/**
 * WebRTC Calls JavaScript Client
 * 
 * Handles audio/video calling functionality for WP Match Free
 * 
 * @package WPMatch_Free
 * @subpackage Assets
 * @since 1.0.0
 */

class WPMFWebRTCClient {
    constructor() {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.currentCall = null;
        this.isInitiator = false;
        this.configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
            ]
        };
        
        this.init();
    }
    
    /**
     * Initialize the WebRTC client
     */
    init() {
        this.setupEventListeners();
        this.checkForPendingCalls();
        
        // Start polling for pending calls every 5 seconds
        setInterval(() => this.checkForPendingCalls(), 5000);
    }
    
    /**
     * Setup event listeners for UI elements
     */
    setupEventListeners() {
        // Call buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wpmf-call-btn')) {
                const recipientId = e.target.dataset.recipientId;
                const callType = e.target.dataset.callType || 'video';
                this.initiateCall(recipientId, callType);
            }
            
            if (e.target.matches('.wpmf-answer-call-btn')) {
                const callId = e.target.dataset.callId;
                this.acceptCall(callId);
            }
            
            if (e.target.matches('.wpmf-decline-call-btn')) {
                const callId = e.target.dataset.callId;
                this.declineCall(callId);
            }
            
            if (e.target.matches('.wpmf-end-call-btn')) {
                this.endCall();
            }
            
            if (e.target.matches('.wpmf-toggle-audio-btn')) {
                this.toggleAudio();
            }
            
            if (e.target.matches('.wpmf-toggle-video-btn')) {
                this.toggleVideo();
            }
        });
        
        // Handle beforeunload to end calls
        window.addEventListener('beforeunload', () => {
            if (this.currentCall && ['active', 'ringing'].includes(this.currentCall.status)) {
                this.endCall();
            }
        });
    }
    
    /**
     * Check for pending incoming calls
     */
    async checkForPendingCalls() {
        try {
            const response = await fetch(`${wpApiSettings.root}wpmatch-free/v1/calls/pending`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce,
                },
            });
            
            if (response.ok) {
                const pendingCalls = await response.json();
                
                if (pendingCalls.length > 0 && (!this.currentCall || this.currentCall.status === 'ended')) {
                    const call = pendingCalls[0]; // Get the most recent call
                    this.showIncomingCallNotification(call);
                }
            }
        } catch (error) {
            console.error('Error checking for pending calls:', error);
        }
    }
    
    /**
     * Initiate a new call
     */
    async initiateCall(recipientId, callType = 'video') {
        try {
            // Get user media first
            const stream = await this.getUserMedia(callType === 'video');
            this.localStream = stream;
            
            // Create the call via API
            const response = await fetch(`${wpApiSettings.root}wpmatch-free/v1/calls`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce,
                },
                body: JSON.stringify({
                    recipient_id: recipientId,
                    call_type: callType,
                }),
            });
            
            if (!response.ok) {
                throw new Error('Failed to create call');
            }
            
            const call = await response.json();
            this.currentCall = call;
            this.isInitiator = true;
            
            // Show calling UI
            this.showCallingInterface(call);
            
            // Initialize peer connection
            await this.initializePeerConnection(call.call_id);
            
            // Create offer
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            
            // Send offer via signaling
            await this.sendSignalingData(call.call_id, {
                type: 'offer',
                sdp: offer,
                timestamp: Date.now(),
            });
            
            // Update call status to ringing
            await this.updateCallStatus(call.call_id, 'ringing');
            
        } catch (error) {
            console.error('Error initiating call:', error);
            this.showError('Failed to start call. Please check your camera/microphone permissions.');
            this.cleanup();
        }
    }
    
    /**
     * Accept an incoming call
     */
    async acceptCall(callId) {
        try {
            const call = await this.getCallDetails(callId);
            if (!call) {
                throw new Error('Call not found');
            }
            
            this.currentCall = call;
            this.isInitiator = false;
            
            // Get user media
            const stream = await this.getUserMedia(call.call_type === 'video');
            this.localStream = stream;
            
            // Hide incoming call notification
            this.hideIncomingCallNotification();
            
            // Show call interface
            this.showCallingInterface(call);
            
            // Initialize peer connection
            await this.initializePeerConnection(callId);
            
            // Update call status to active
            await this.updateCallStatus(callId, 'active');
            
            // Start polling for signaling data
            this.startSignalingPolling(callId);
            
        } catch (error) {
            console.error('Error accepting call:', error);
            this.showError('Failed to accept call. Please check your camera/microphone permissions.');
            await this.declineCall(callId);
        }
    }
    
    /**
     * Decline an incoming call
     */
    async declineCall(callId) {
        try {
            await this.updateCallStatus(callId, 'declined');
            this.hideIncomingCallNotification();
            this.cleanup();
        } catch (error) {
            console.error('Error declining call:', error);
        }
    }
    
    /**
     * End the current call
     */
    async endCall() {
        try {
            if (this.currentCall) {
                await this.updateCallStatus(this.currentCall.call_id, 'ended', 'user_ended');
            }
            
            this.hideCallInterface();
            this.cleanup();
            
        } catch (error) {
            console.error('Error ending call:', error);
        }
    }
    
    /**
     * Initialize WebRTC peer connection
     */
    async initializePeerConnection(callId) {
        this.peerConnection = new RTCPeerConnection(this.configuration);
        
        // Add local stream to peer connection
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
        }
        
        // Handle remote stream
        this.peerConnection.ontrack = (event) => {
            this.remoteStream = event.streams[0];
            const remoteVideo = document.getElementById('wpmf-remote-video');
            if (remoteVideo) {
                remoteVideo.srcObject = this.remoteStream;
            }
        };
        
        // Handle ICE candidates
        this.peerConnection.onicecandidate = async (event) => {
            if (event.candidate) {
                await this.sendSignalingData(callId, {
                    type: 'ice-candidate',
                    candidate: event.candidate,
                    timestamp: Date.now(),
                });
            }
        };
        
        // Handle connection state changes
        this.peerConnection.onconnectionstatechange = () => {
            console.log('Connection state:', this.peerConnection.connectionState);
            
            if (this.peerConnection.connectionState === 'connected') {
                this.showConnectedState();
            } else if (this.peerConnection.connectionState === 'failed' || 
                      this.peerConnection.connectionState === 'disconnected') {
                this.handleConnectionFailure();
            }
        };
        
        // Start signaling polling for the initiator too
        if (this.isInitiator) {
            this.startSignalingPolling(callId);
        }
    }
    
    /**
     * Start polling for signaling data
     */
    startSignalingPolling(callId) {
        const pollSignaling = async () => {
            try {
                const call = await this.getCallDetails(callId);
                
                if (!call || call.status === 'ended' || call.status === 'declined' || call.status === 'missed') {
                    this.cleanup();
                    return;
                }
                
                if (call.signaling_data) {
                    await this.processSignalingData(call.signaling_data);
                }
                
                // Continue polling if call is still active
                if (['pending', 'ringing', 'active'].includes(call.status)) {
                    setTimeout(pollSignaling, 1000); // Poll every second
                }
                
            } catch (error) {
                console.error('Error polling signaling data:', error);
                setTimeout(pollSignaling, 2000); // Retry after 2 seconds on error
            }
        };
        
        pollSignaling();
    }
    
    /**
     * Process incoming signaling data
     */
    async processSignalingData(signalingData) {
        if (!this.peerConnection) return;
        
        for (const data of signalingData) {
            if (data.processed) continue;
            
            try {
                switch (data.type) {
                    case 'offer':
                        if (!this.isInitiator) {
                            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                            const answer = await this.peerConnection.createAnswer();
                            await this.peerConnection.setLocalDescription(answer);
                            
                            await this.sendSignalingData(this.currentCall.call_id, {
                                type: 'answer',
                                sdp: answer,
                                timestamp: Date.now(),
                            });
                        }
                        break;
                        
                    case 'answer':
                        if (this.isInitiator) {
                            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                        }
                        break;
                        
                    case 'ice-candidate':
                        await this.peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                        break;
                }
                
                // Mark as processed
                data.processed = true;
                
            } catch (error) {
                console.error('Error processing signaling data:', error);
            }
        }
    }
    
    /**
     * Send signaling data to the other peer
     */
    async sendSignalingData(callId, data) {
        try {
            const response = await fetch(`${wpApiSettings.root}wpmatch-free/v1/calls/${callId}/signaling`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce,
                },
                body: JSON.stringify({
                    signaling_data: data,
                }),
            });
            
            if (!response.ok) {
                throw new Error('Failed to send signaling data');
            }
            
        } catch (error) {
            console.error('Error sending signaling data:', error);
            throw error;
        }
    }
    
    /**
     * Update call status
     */
    async updateCallStatus(callId, status, endReason = null) {
        try {
            const body = { status };
            if (endReason) {
                body.end_reason = endReason;
            }
            
            const response = await fetch(`${wpApiSettings.root}wpmatch-free/v1/calls/${callId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce,
                },
                body: JSON.stringify(body),
            });
            
            if (!response.ok) {
                throw new Error('Failed to update call status');
            }
            
            const updatedCall = await response.json();
            this.currentCall = updatedCall;
            
        } catch (error) {
            console.error('Error updating call status:', error);
            throw error;
        }
    }
    
    /**
     * Get call details
     */
    async getCallDetails(callId) {
        try {
            const response = await fetch(`${wpApiSettings.root}wpmatch-free/v1/calls/${callId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce,
                },
            });
            
            if (!response.ok) {
                return null;
            }
            
            return await response.json();
            
        } catch (error) {
            console.error('Error getting call details:', error);
            return null;
        }
    }
    
    /**
     * Get user media (camera/microphone)
     */
    async getUserMedia(includeVideo = true) {
        const constraints = {
            audio: true,
            video: includeVideo,
        };
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            
            // Show local video if available
            const localVideo = document.getElementById('wpmf-local-video');
            if (localVideo && includeVideo) {
                localVideo.srcObject = stream;
                localVideo.muted = true; // Prevent feedback
            }
            
            return stream;
            
        } catch (error) {
            console.error('Error accessing media devices:', error);
            throw new Error('Could not access camera/microphone. Please check your permissions.');
        }
    }
    
    /**
     * Toggle audio on/off
     */
    toggleAudio() {
        if (this.localStream) {
            const audioTracks = this.localStream.getAudioTracks();
            audioTracks.forEach(track => {
                track.enabled = !track.enabled;
            });
            
            const btn = document.querySelector('.wpmf-toggle-audio-btn');
            if (btn) {
                btn.classList.toggle('muted', !audioTracks[0]?.enabled);
                btn.innerHTML = audioTracks[0]?.enabled ? 
                    '<i class="fas fa-microphone"></i>' : 
                    '<i class="fas fa-microphone-slash"></i>';
            }
        }
    }
    
    /**
     * Toggle video on/off
     */
    toggleVideo() {
        if (this.localStream) {
            const videoTracks = this.localStream.getVideoTracks();
            videoTracks.forEach(track => {
                track.enabled = !track.enabled;
            });
            
            const btn = document.querySelector('.wpmf-toggle-video-btn');
            const localVideo = document.getElementById('wpmf-local-video');
            
            if (btn) {
                btn.classList.toggle('muted', !videoTracks[0]?.enabled);
                btn.innerHTML = videoTracks[0]?.enabled ? 
                    '<i class="fas fa-video"></i>' : 
                    '<i class="fas fa-video-slash"></i>';
            }
            
            if (localVideo) {
                localVideo.style.display = videoTracks[0]?.enabled ? 'block' : 'none';
            }
        }
    }
    
    /**
     * Show incoming call notification
     */
    showIncomingCallNotification(call) {
        // Remove existing notifications first
        this.hideIncomingCallNotification();
        
        const notification = document.createElement('div');
        notification.id = 'wpmf-incoming-call-notification';
        notification.className = 'wpmf-incoming-call-notification';
        notification.innerHTML = `
            <div class="wpmf-call-notification-content">
                <div class="wpmf-call-info">
                    <h3>Incoming ${call.call_type} call</h3>
                    <p>From: ${call.other_user.display_name}</p>
                </div>
                <div class="wpmf-call-actions">
                    <button class="wpmf-answer-call-btn btn-success" data-call-id="${call.call_id}">
                        <i class="fas fa-phone"></i> Answer
                    </button>
                    <button class="wpmf-decline-call-btn btn-danger" data-call-id="${call.call_id}">
                        <i class="fas fa-phone-slash"></i> Decline
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-decline after 30 seconds
        setTimeout(() => {
            if (document.getElementById('wpmf-incoming-call-notification')) {
                this.declineCall(call.call_id);
            }
        }, 30000);
    }
    
    /**
     * Hide incoming call notification
     */
    hideIncomingCallNotification() {
        const notification = document.getElementById('wpmf-incoming-call-notification');
        if (notification) {
            notification.remove();
        }
    }
    
    /**
     * Show calling interface
     */
    showCallingInterface(call) {
        // Remove existing interface first
        this.hideCallInterface();
        
        const callInterface = document.createElement('div');
        callInterface.id = 'wpmf-call-interface';
        callInterface.className = 'wpmf-call-interface';
        callInterface.innerHTML = `
            <div class="wpmf-call-container">
                <div class="wpmf-call-header">
                    <h3>${call.call_type === 'video' ? 'Video' : 'Audio'} Call</h3>
                    <p class="wpmf-call-status">Connecting...</p>
                    <p class="wpmf-call-participant">${call.other_user.display_name}</p>
                </div>
                <div class="wpmf-video-container">
                    <video id="wpmf-remote-video" class="wpmf-remote-video" autoplay playsinline></video>
                    <video id="wpmf-local-video" class="wpmf-local-video" autoplay playsinline muted></video>
                </div>
                <div class="wpmf-call-controls">
                    <button class="wpmf-toggle-audio-btn btn-control">
                        <i class="fas fa-microphone"></i>
                    </button>
                    ${call.call_type === 'video' ? `
                        <button class="wpmf-toggle-video-btn btn-control">
                            <i class="fas fa-video"></i>
                        </button>
                    ` : ''}
                    <button class="wpmf-end-call-btn btn-danger">
                        <i class="fas fa-phone-slash"></i> End Call
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(callInterface);
        
        // Show local video if available
        if (this.localStream && call.call_type === 'video') {
            const localVideo = document.getElementById('wpmf-local-video');
            if (localVideo) {
                localVideo.srcObject = this.localStream;
            }
        }
    }
    
    /**
     * Hide call interface
     */
    hideCallInterface() {
        const callInterface = document.getElementById('wpmf-call-interface');
        if (callInterface) {
            callInterface.remove();
        }
    }
    
    /**
     * Show connected state
     */
    showConnectedState() {
        const statusElement = document.querySelector('.wpmf-call-status');
        if (statusElement) {
            statusElement.textContent = 'Connected';
            statusElement.classList.add('connected');
        }
    }
    
    /**
     * Handle connection failure
     */
    handleConnectionFailure() {
        this.showError('Connection failed. The call will be ended.');
        setTimeout(() => this.endCall(), 2000);
    }
    
    /**
     * Show error message
     */
    showError(message) {
        // Create or update error notification
        let errorDiv = document.getElementById('wpmf-call-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'wpmf-call-error';
            errorDiv.className = 'wpmf-call-error';
            document.body.appendChild(errorDiv);
        }
        
        errorDiv.innerHTML = `
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorDiv) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    /**
     * Cleanup resources
     */
    cleanup() {
        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }
        
        // Close peer connection
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        
        // Clear remote stream
        this.remoteStream = null;
        
        // Reset state
        this.currentCall = null;
        this.isInitiator = false;
        
        // Hide UI elements
        this.hideCallInterface();
        this.hideIncomingCallNotification();
    }
}

// Initialize WebRTC client when page loads
document.addEventListener('DOMContentLoaded', () => {
    if (typeof wpApiSettings !== 'undefined') {
        window.wpmfWebRTC = new WPMFWebRTCClient();
    }
});