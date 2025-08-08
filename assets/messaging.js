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
        typingTimeout: null,
        typingPolling: null,
        isTyping: false,

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
                this.startTypingPolling();
            }
        },

        bindEvents: function() {
            // Send message form
            $(document).on('submit', '#wpmf-send-message-form', this.sendMessage.bind(this));
            
            // Conversation selection
            $(document).on('click', '.wpmf-conversation-item', this.selectConversation.bind(this));
            
            // Message input auto-resize and typing detection
            $(document).on('input', '#wpmf-message-input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
                
                // Handle typing indicator
                WPMFMessaging.handleTyping();
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
                conversationsList.html('<div class="flex flex-col items-center justify-center h-64 text-center p-6">' +
                    '<div class="text-6xl mb-4">💭</div>' +
                    '<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No conversations yet</h3>' +
                    '<p class="text-sm text-gray-500 dark:text-gray-400">Start messaging other users to see conversations here.</p>' +
                '</div>');
                return;
            }

            let html = '<div class="divide-y divide-gray-100 dark:divide-gray-800">';
            conversations.forEach(function(conv) {
                const isUnread = parseInt(conv.unread_count) > 0;
                const otherUser = conv.other_user || {};
                const timeAgo = WPMFMessaging.timeAgo(conv.last_message_time);
                
                html += '<div class="wpmf-conversation-item group relative flex items-center p-4 hover:bg-white dark:hover:bg-gray-800 cursor-pointer transition-all duration-200' + 
                        (isUnread ? ' bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : '') + 
                        '" data-thread-id="' + conv.thread_id + '" data-user-id="' + conv.other_user_id + '">' +
                    '<div class="relative flex-shrink-0 mr-3">' +
                        '<img src="' + (otherUser.avatar || '') + '" alt="' + (otherUser.display_name || 'User') + 
                        '" class="w-12 h-12 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 shadow-sm">' +
                        (isUnread ? '<div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white dark:border-gray-800"></div>' : '') +
                        '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white dark:border-gray-800"></div>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<div class="flex items-center justify-between mb-1">' +
                            '<h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">' + 
                            (otherUser.display_name || 'Unknown User') + '</h4>' +
                            '<span class="text-xs text-gray-500 dark:text-gray-400 ml-2">' + timeAgo + '</span>' +
                        '</div>' +
                        '<div class="flex items-center justify-between">' +
                            '<p class="text-sm text-gray-600 dark:text-gray-400 truncate flex-1">' + 
                            WPMFMessaging.truncate(conv.last_message, 45) + '</p>' +
                            (isUnread ? '<span class="ml-2 bg-indigo-500 text-white text-xs font-bold px-2 py-1 rounded-full">' + 
                            conv.unread_count + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="absolute right-2 top-1/2 transform -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">' +
                        '<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">' +
                            '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>' +
                        '</svg>' +
                    '</div>' +
                '</div>';
            });
            html += '</div>';

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
            this.startTypingPolling();
            
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
                messagesList.html('<div class="flex flex-col items-center justify-center h-64 text-center p-8">' +
                    '<div class="text-6xl mb-4">💬</div>' +
                    '<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No messages yet</h3>' +
                    '<p class="text-sm text-gray-500 dark:text-gray-400">Send the first message to start the conversation!</p>' +
                '</div>');
                return;
            }

            let html = '<div class="space-y-4 p-4">';
            let currentDate = '';

            messages.forEach(function(message) {
                const messageDate = new Date(message.created_at).toDateString();
                const isOwn = parseInt(message.sender_id) === parseInt(wpmf_ajax.user_id);
                const sender = message.sender || {};

                // Add date separator
                if (messageDate !== currentDate) {
                    html += '<div class="flex items-center justify-center my-6">' +
                        '<div class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full text-xs font-medium text-gray-600 dark:text-gray-300">' +
                        WPMFMessaging.formatDate(message.created_at) + '</div>' +
                    '</div>';
                    currentDate = messageDate;
                }

                html += '<div class="wpmf-message flex ' + (isOwn ? 'justify-end' : 'justify-start') + '" data-message-id="' + message.id + '">' +
                    '<div class="flex items-end space-x-2 max-w-xs lg:max-w-md' + (isOwn ? ' flex-row-reverse space-x-reverse' : '') + '">';
                
                // Avatar for other users
                if (!isOwn) {
                    html += '<img src="' + (sender.avatar || '') + '" alt="' + (sender.display_name || 'User') + 
                           '" class="w-8 h-8 rounded-full object-cover flex-shrink-0">';
                }
                
                html += '<div class="relative group">' +
                    '<div class="px-4 py-2 rounded-2xl shadow-sm ' + 
                    (isOwn ? 'bg-indigo-500 text-white rounded-br-md' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-bl-md') + '">' +
                        '<div class="text-sm break-words">' + WPMFMessaging.linkify(message.body) + '</div>' +
                    '</div>' +
                    '<div class="flex items-center justify-' + (isOwn ? 'end' : 'start') + ' mt-1 px-1 space-x-1 opacity-60 group-hover:opacity-100 transition-opacity">' +
                        '<span class="text-xs text-gray-500 dark:text-gray-400">' + WPMFMessaging.formatTime(message.created_at) + '</span>';
                        
                if (isOwn) {
                    if (message.status === 'read') {
                        html += '<span class="text-xs text-indigo-400">✓✓</span>';
                    } else if (message.status === 'delivered') {
                        html += '<span class="text-xs text-gray-400">✓</span>';
                    }
                }
                        
                html += '</div>' +
                '</div>' +
                '</div>' +
                '</div>';
                
                // Track last message ID for polling
                if (parseInt(message.id) > WPMFMessaging.lastMessageId) {
                    WPMFMessaging.lastMessageId = parseInt(message.id);
                }
            });
            
            html += '</div>';
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
                const userAvatar = conversationItem.find('img').attr('src');
                
                header.html('<div class="flex items-center space-x-4 p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">' +
                    '<div class="relative">' +
                        '<img src="' + userAvatar + '" alt="' + userName + '" class="w-12 h-12 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 shadow-lg">' +
                        '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white dark:border-gray-800"></div>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">' + userName + '</h3>' +
                        '<p class="text-sm text-green-500 dark:text-green-400 font-medium">Online</p>' +
                    '</div>' +
                    '<div class="flex items-center space-x-2">' +
                        '<button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">' +
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">' +
                                '<path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>' +
                            '</svg>' +
                        '</button>' +
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

            // Stop typing indicator and disable form
            this.stopTyping();
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
            
            const messageHtml = '<div class="wpmf-message flex ' + (isOwn ? 'justify-end' : 'justify-start') + '" data-message-id="' + message.id + '">' +
                '<div class="flex items-end space-x-2 max-w-xs lg:max-w-md' + (isOwn ? ' flex-row-reverse space-x-reverse' : '') + '">';
            
            // Avatar for other users
            let avatarHtml = '';
            if (!isOwn) {
                avatarHtml = '<img src="' + (sender.avatar || '') + '" alt="' + (sender.display_name || 'User') + 
                           '" class="w-8 h-8 rounded-full object-cover flex-shrink-0">';
            }
            
            const bubbleHtml = '<div class="relative group">' +
                '<div class="px-4 py-2 rounded-2xl shadow-sm ' + 
                (isOwn ? 'bg-indigo-500 text-white rounded-br-md' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-bl-md') + '">' +
                    '<div class="text-sm break-words">' + this.linkify(message.body) + '</div>' +
                '</div>' +
                '<div class="flex items-center justify-' + (isOwn ? 'end' : 'start') + ' mt-1 px-1 space-x-1 opacity-60 group-hover:opacity-100 transition-opacity">' +
                    '<span class="text-xs text-gray-500 dark:text-gray-400">' + this.formatTime(message.created_at) + '</span>' +
                '</div>' +
            '</div>';
            
            const fullMessageHtml = messageHtml + avatarHtml + bubbleHtml + '</div></div>';

            // Remove no messages placeholder if exists and ensure we have the right container
            const noMessagesPlaceholder = messagesList.find('.flex.flex-col.items-center');
            if (noMessagesPlaceholder.length) {
                noMessagesPlaceholder.remove();
                messagesList.html('<div class="space-y-4 p-4"></div>');
            }
            
            // Find the messages container or create it
            let messagesContainer = messagesList.find('.space-y-4');
            if (!messagesContainer.length) {
                messagesList.html('<div class="space-y-4 p-4"></div>');
                messagesContainer = messagesList.find('.space-y-4');
            }
            
            messagesContainer.append(fullMessageHtml);
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
            // Create modern toast notification with Tailwind
            const toast = $('<div class="fixed top-4 right-4 z-50 max-w-sm">' +
                '<div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-pulse">' +
                    '<svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">' +
                        '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>' +
                    '</svg>' +
                    '<div>' +
                        '<p class="font-medium">Error</p>' +
                        '<p class="text-sm opacity-90">' + message + '</p>' +
                    '</div>' +
                    '<button class="ml-auto flex-shrink-0 p-1 hover:bg-red-600 rounded" onclick="$(this).closest(\'div\').fadeOut()">' +
                        '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' +
                            '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>' +
                        '</svg>' +
                    '</button>' +
                '</div>' +
            '</div>');
            
            $('body').append(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                toast.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Typing indicator methods
        handleTyping: function() {
            if (!this.currentThreadId) return;
            
            // Clear existing timeout
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }
            
            // If not already typing, send typing start
            if (!this.isTyping) {
                this.setTypingStatus(true);
                this.isTyping = true;
            }
            
            // Set timeout to stop typing after 3 seconds of no input
            this.typingTimeout = setTimeout(function() {
                WPMFMessaging.setTypingStatus(false);
                WPMFMessaging.isTyping = false;
            }, 3000);
        },

        setTypingStatus: function(isTyping) {
            if (!this.currentThreadId) return;
            
            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations/' + this.currentThreadId + '/typing',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                data: {
                    is_typing: isTyping
                }
                // Silent - no error handling needed for typing indicators
            });
        },

        startTypingPolling: function() {
            if (this.typingPolling) {
                clearInterval(this.typingPolling);
            }
            
            // Poll for typing indicators every 2 seconds
            this.typingPolling = setInterval(function() {
                if (WPMFMessaging.currentThreadId) {
                    WPMFMessaging.checkTypingStatus();
                }
            }, 2000);
        },

        checkTypingStatus: function() {
            if (!this.currentThreadId) return;
            
            $.ajax({
                url: wpmf_ajax.rest_url + 'conversations/' + this.currentThreadId + '/typing-status',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpmf_ajax.nonce);
                },
                success: function(response) {
                    WPMFMessaging.updateTypingIndicator(response.typing_users);
                }
                // Silent - no error handling needed
            });
        },

        updateTypingIndicator: function(typingUsers) {
            const messagesList = $('#wpmf-messages-list');
            const existingIndicator = messagesList.find('.wpmf-typing-indicator');
            
            if (!typingUsers || typingUsers.length === 0) {
                // Remove typing indicator if no one is typing
                existingIndicator.remove();
                return;
            }
            
            // Create typing indicator HTML with Tailwind classes
            let html = '<div class="wpmf-typing-indicator flex items-center space-x-3 p-4 mx-4 mb-4">';
            
            // Avatars container
            html += '<div class="flex -space-x-1">';
            
            // Show up to 3 avatars
            typingUsers.slice(0, 3).forEach(function(typingUser, index) {
                if (typingUser.user && typingUser.user.avatar) {
                    html += '<img src="' + typingUser.user.avatar + '" alt="' + 
                           (typingUser.user.display_name || 'User') + 
                           '" class="w-6 h-6 rounded-full object-cover border-2 border-white dark:border-gray-800 ' +
                           (index > 0 ? 'ml-2' : '') + '">';
                }
            });
            
            html += '</div>';
            
            // Typing bubble
            html += '<div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm max-w-xs">' +
                '<div class="flex items-center space-x-1">' +
                    '<span class="text-sm text-gray-600 dark:text-gray-300">';
            
            if (typingUsers.length === 1) {
                html += (typingUsers[0].user?.display_name || 'Someone') + ' is typing';
            } else if (typingUsers.length === 2) {
                html += (typingUsers[0].user?.display_name || 'Someone') + ' and ' + 
                       (typingUsers[1].user?.display_name || 'someone else') + ' are typing';
            } else {
                html += typingUsers.length + ' people are typing';
            }
            
            html += '</span>' +
                    '<div class="flex space-x-1 ml-2">' +
                        '<div class="w-1.5 h-1.5 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>' +
                        '<div class="w-1.5 h-1.5 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>' +
                        '<div class="w-1.5 h-1.5 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '</div>';
            
            if (existingIndicator.length) {
                // Update existing indicator
                existingIndicator.replaceWith(html);
            } else {
                // Add new indicator at the end of messages container
                const messagesContainer = messagesList.find('.space-y-4');
                if (messagesContainer.length) {
                    messagesContainer.append(html);
                } else {
                    messagesList.append(html);
                }
            }
            
            // Auto-scroll to show typing indicator
            this.scrollToBottom();
        },

        stopTyping: function() {
            if (this.isTyping) {
                this.setTypingStatus(false);
                this.isTyping = false;
            }
            
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
                this.typingTimeout = null;
            }
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
        if (WPMFMessaging.typingPolling) {
            clearInterval(WPMFMessaging.typingPolling);
        }
        // Stop typing indicator when leaving
        WPMFMessaging.stopTyping();
    });

})(jQuery);