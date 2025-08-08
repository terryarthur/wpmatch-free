/**
 * WP Match Free - Messaging JavaScript
 * Real-time messaging interface with REST API integration
 * 
 * @package WPMatchFree
 * @version 0.1.0
 */

(function($) {
    'use strict';

    // Global messaging object
    window.WPMFMessaging = {
        currentThreadId: null,
        currentRecipientId: null,
        pollingInterval: null,
        unreadPolling: null,
        isComposing: false,
        lastMessageId: 0,

        init: function() {
            this.bindEvents();
            this.loadConversations();
            this.startUnreadCountPolling();
            
            // Auto-scroll to bottom of messages
            this.scrollToBottom();
            
            // Handle single conversation view
            const conversationContainer = $('#wpmf-conversation-container');
            if (conversationContainer.length) {
                this.currentThreadId = conversationContainer.data('thread-id');
                this.loadMessages(this.currentThreadId);
                this.startMessagePolling();
            }
        },

        bindEvents: function() {
            // Send message form
            $(document).on('submit', '#wpmf-send-message-form', this.sendMessage.bind(this));
            
            // Conversation selection
            $(document).on('click', '.wpmf-conversation-item', this.selectConversation.bind(this));
            
            // Message input auto-resize
            $(document).on('input', '#wpmf-message-input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
            
            // Enter key to send (Ctrl+Enter for new line)
            $(document).on('keydown', '#wpmf-message-input', function(e) {
                if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                    e.preventDefault();
                    $('#wpmf-send-message-form').submit();
                }
            });
            
            // Mark messages as read when conversation is viewed
            $(document).on('click', '.wpmf-conversation-item', function() {
                const threadId = $(this).data('thread-id');
                if (threadId) {
                    WPMFMessaging.markAsRead(threadId);
                }
            });
        },

        loadConversations: function() {
            const conversationsList = $('#wpmf-conversations-list');
            if (!conversationsList.length) return;

            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function(data) {
                    WPMFMessaging.renderConversations(data);
                },
                error: function(xhr) {
                    conversationsList.html('<div class="wpmf-error">' + 
                        (xhr.responseJSON?.message || 'Failed to load conversations.') + 
                    '</div>');
                }
            });
        },

        renderConversations: function(conversations) {
            const conversationsList = $('#wpmf-conversations-list');
            
            if (!conversations || conversations.length === 0) {
                conversationsList.html('<div class="wpmf-no-conversations">' +
                    '<div class="wpmf-icon">💭</div>' +
                    '<p>No conversations yet.</p>' +
                    '<p class="wpmf-small">Start messaging other users to see conversations here.</p>' +
                '</div>');
                return;
            }

            let html = '';
            conversations.forEach(function(conv) {
                const isUnread = parseInt(conv.unread_count) > 0;
                const otherUser = conv.other_user || {};
                const timeAgo = WPMFMessaging.timeAgo(conv.last_message_time);
                
                html += '<div class="wpmf-conversation-item' + (isUnread ? ' wpmf-unread' : '') + 
                        '" data-thread-id="' + conv.thread_id + '" data-user-id="' + conv.other_user_id + '">' +
                    '<div class="wpmf-conversation-avatar">' +
                        '<img src="' + (otherUser.avatar || '') + '" alt="' + (otherUser.display_name || 'User') + '">' +
                        (isUnread ? '<div class="wpmf-unread-indicator"></div>' : '') +
                    '</div>' +
                    '<div class="wpmf-conversation-content">' +
                        '<div class="wpmf-conversation-header">' +
                            '<h4>' + (otherUser.display_name || 'Unknown User') + '</h4>' +
                            '<span class="wpmf-conversation-time">' + timeAgo + '</span>' +
                        '</div>' +
                        '<div class="wpmf-conversation-preview">' +
                            '<span class="wpmf-last-message">' + WPMFMessaging.truncate(conv.last_message, 60) + '</span>' +
                            (isUnread ? '<div class="wpmf-unread-count">' + conv.unread_count + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });

            conversationsList.html(html);
        },

        selectConversation: function(e) {
            e.preventDefault();
            const item = $(e.currentTarget);
            const threadId = item.data('thread-id');
            const userId = item.data('user-id');

            if (this.currentThreadId === threadId) return;

            // Update UI
            $('.wpmf-conversation-item').removeClass('wpmf-active');
            item.addClass('wpmf-active');

            this.currentThreadId = threadId;
            this.currentRecipientId = userId;

            // Load messages for selected conversation
            this.loadMessages(threadId);
            this.startMessagePolling();
            
            // Mark as read
            this.markAsRead(threadId);
        },

        loadMessages: function(threadId) {
            const messagesList = $('#wpmf-messages-list');
            if (!messagesList.length) return;

            messagesList.html('<div class="wpmf-loading">Loading messages...</div>');

            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations/' + threadId + '/messages',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function(data) {
                    WPMFMessaging.renderMessages(data);
                    WPMFMessaging.updateConversationHeader(threadId);
                },
                error: function(xhr) {
                    messagesList.html('<div class="wpmf-error">' + 
                        (xhr.responseJSON?.message || 'Failed to load messages.') + 
                    '</div>');
                }
            });
        },

        renderMessages: function(messages) {
            const messagesList = $('#wpmf-messages-list');
            
            if (!messages || messages.length === 0) {
                messagesList.html('<div class="wpmf-no-messages">' +
                    '<div class="wpmf-icon">💬</div>' +
                    '<p>No messages in this conversation.</p>' +
                    '<p class="wpmf-small">Send the first message to start the conversation!</p>' +
                '</div>');
                return;
            }

            let html = '';
            let currentDate = '';

            messages.forEach(function(message) {
                const messageDate = new Date(message.created_at).toDateString();
                const isOwn = parseInt(message.sender_id) === parseInt(wpmf_ajax.user_id);
                const sender = message.sender || {};

                // Add date separator
                if (messageDate !== currentDate) {
                    html += '<div class="wpmf-date-separator">' +
                        '<span>' + WPMFMessaging.formatDate(message.created_at) + '</span>' +
                    '</div>';
                    currentDate = messageDate;
                }

                html += '<div class="wpmf-message' + (isOwn ? ' wpmf-message-own' : ' wpmf-message-other') + 
                        '" data-message-id="' + message.id + '">' +
                    '<div class="wpmf-message-content">' +
                        (!isOwn ? '<div class="wpmf-message-avatar">' +
                            '<img src="' + (sender.avatar || '') + '" alt="' + (sender.display_name || 'User') + '">' +
                        '</div>' : '') +
                        '<div class="wpmf-message-bubble">' +
                            '<div class="wpmf-message-text">' + WPMFMessaging.linkify(message.body) + '</div>' +
                            '<div class="wpmf-message-meta">' +
                                '<span class="wpmf-message-time">' + WPMFMessaging.formatTime(message.created_at) + '</span>' +
                                (message.status === 'read' ? '<span class="wpmf-message-status">✓✓</span>' : 
                                 message.status === 'delivered' ? '<span class="wpmf-message-status">✓</span>' : '') +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                // Track last message ID for polling
                if (parseInt(message.id) > WPMFMessaging.lastMessageId) {
                    WPMFMessaging.lastMessageId = parseInt(message.id);
                }
            });

            messagesList.html(html);
            this.scrollToBottom();
        },

        updateConversationHeader: function(threadId) {
            const header = $('#wpmf-conversation-header');
            if (!header.length) return;

            // Find conversation info from the conversation list
            const conversationItem = $('.wpmf-conversation-item[data-thread-id="' + threadId + '"]');
            if (conversationItem.length) {
                const userName = conversationItem.find('h4').text();
                const userAvatar = conversationItem.find('.wpmf-conversation-avatar img').attr('src');
                
                header.html('<div class="wpmf-conversation-info">' +
                    '<div class="wpmf-conversation-avatar">' +
                        '<img src="' + userAvatar + '" alt="' + userName + '">' +
                    '</div>' +
                    '<div class="wpmf-conversation-details">' +
                        '<h3>' + userName + '</h3>' +
                        '<span class="wpmf-conversation-status">Online</span>' +
                    '</div>' +
                '</div>');
            }
        },

        sendMessage: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const input = $('#wpmf-message-input');
            const message = input.val().trim();
            const sendButton = $('.wpmf-send-button');
            
            if (!message) return;
            
            if (!this.currentRecipientId && !this.currentThreadId) {
                this.showError('No conversation selected.');
                return;
            }

            // Disable form
            sendButton.prop('disabled', true);
            $('.wpmf-send-text').hide();
            $('.wpmf-send-loading').show();

            $.ajax({
                url: wpmf_ajax.rest_url + 'messages',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                data: {
                    recipient_id: this.currentRecipientId,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        input.val('');
                        input.css('height', 'auto');
                        
                        // Add message to UI immediately for better UX
                        if (response.data) {
                            WPMFMessaging.addMessageToUI(response.data);
                        }
                        
                        // Refresh conversations to update last message
                        WPMFMessaging.loadConversations();
                    } else {
                        WPMFMessaging.showError(response.message || 'Failed to send message.');
                    }
                },
                error: function(xhr) {
                    WPMFMessaging.showError(xhr.responseJSON?.message || 'Failed to send message.');
                },
                complete: function() {
                    sendButton.prop('disabled', false);
                    $('.wpmf-send-text').show();
                    $('.wpmf-send-loading').hide();
                }
            });
        },

        addMessageToUI: function(message) {
            const messagesList = $('#wpmf-messages-list');
            const isOwn = parseInt(message.sender_id) === parseInt(wpmf_ajax.user_id);
            const sender = message.sender || {};
            
            const messageHtml = '<div class="wpmf-message' + (isOwn ? ' wpmf-message-own' : ' wpmf-message-other') + 
                    '" data-message-id="' + message.id + '">' +
                '<div class="wpmf-message-content">' +
                    (!isOwn ? '<div class="wpmf-message-avatar">' +
                        '<img src="' + (sender.avatar || '') + '" alt="' + (sender.display_name || 'User') + '">' +
                    '</div>' : '') +
                    '<div class="wpmf-message-bubble">' +
                        '<div class="wpmf-message-text">' + this.linkify(message.body) + '</div>' +
                        '<div class="wpmf-message-meta">' +
                            '<span class="wpmf-message-time">' + this.formatTime(message.created_at) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

            // Remove no messages placeholder if exists
            messagesList.find('.wpmf-no-messages').remove();
            
            messagesList.append(messageHtml);
            this.scrollToBottom();
            
            // Update last message ID
            if (parseInt(message.id) > this.lastMessageId) {
                this.lastMessageId = parseInt(message.id);
            }
        },

        markAsRead: function(threadId) {
            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations/' + threadId + '/read',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function() {
                    // Remove unread indicators from UI
                    const conversationItem = $('.wpmf-conversation-item[data-thread-id="' + threadId + '"]');
                    conversationItem.removeClass('wpmf-unread');
                    conversationItem.find('.wpmf-unread-indicator, .wpmf-unread-count').remove();
                    
                    // Update global unread count
                    WPMFMessaging.updateUnreadCount();
                }
            });
        },

        startMessagePolling: function() {
            // Clear existing polling
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }

            // Poll for new messages every 3 seconds when viewing a conversation
            this.pollingInterval = setInterval(function() {
                if (WPMFMessaging.currentThreadId) {
                    WPMFMessaging.checkForNewMessages();
                }
            }, 3000);
        },

        checkForNewMessages: function() {
            if (!this.currentThreadId) return;

            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations/' + this.currentThreadId + '/messages',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function(messages) {
                    // Check if there are new messages
                    let hasNewMessages = false;
                    if (messages && messages.length > 0) {
                        messages.forEach(function(message) {
                            if (parseInt(message.id) > WPMFMessaging.lastMessageId) {
                                hasNewMessages = true;
                            }
                        });
                    }
                    
                    if (hasNewMessages) {
                        WPMFMessaging.renderMessages(messages);
                        WPMFMessaging.markAsRead(WPMFMessaging.currentThreadId);
                    }
                }
            });
        },

        startUnreadCountPolling: function() {
            this.updateUnreadCount();
            
            // Poll for unread count every 10 seconds
            this.unreadPolling = setInterval(function() {
                WPMFMessaging.updateUnreadCount();
            }, 10000);
        },

        updateUnreadCount: function() {
            $.ajax({
                url: wpmf_ajax.rest_url + 'messages/unread-count',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function(response) {
                    const count = parseInt(response.count) || 0;
                    const badge = $('#wpmf-unread-count');
                    
                    if (count > 0) {
                        badge.find('span').text(count);
                        badge.show();
                    } else {
                        badge.hide();
                    }
                }
            });
        },

        scrollToBottom: function() {
            const messagesList = $('#wpmf-messages-list');
            if (messagesList.length) {
                messagesList.scrollTop(messagesList[0].scrollHeight);
            }
        },

        showError: function(message) {
            // Simple error notification - could be enhanced with a proper toast system
            alert('Error: ' + message);
        },

        // Utility functions
        timeAgo: function(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + 'd ago';
            
            return date.toLocaleDateString();
        },

        formatDate: function(dateString) {
            const date = new Date(dateString);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (date.toDateString() === today.toDateString()) {
                return 'Today';
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            } else {
                return date.toLocaleDateString();
            }
        },

        formatTime: function(dateString) {
            return new Date(dateString).toLocaleTimeString([], {
                hour: '2-digit', 
                minute: '2-digit'
            });
        },

        truncate: function(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },

        linkify: function(text) {
            // Simple URL linkification
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            return text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#wpmf-messages-container').length || $('#wpmf-conversation-container').length) {
            WPMFMessaging.init();
        }
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (WPMFMessaging.pollingInterval) {
            clearInterval(WPMFMessaging.pollingInterval);
        }
        if (WPMFMessaging.unreadPolling) {
            clearInterval(WPMFMessaging.unreadPolling);
        }
    });

})(jQuery);