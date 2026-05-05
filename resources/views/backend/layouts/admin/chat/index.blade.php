@extends('backend.app')
@section('title', 'Admin Chat')

@section('title', 'Dealer Chat')

@push('styles')
<style>
    .chat-wrap {
        border: 1px solid #d7e3ef;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        min-height: 72vh;
    }

    .chat-left {
        border-right: 1px solid #e5edf5;
        background: #f8fbff;
    }

    .chat-user {
        cursor: pointer;
        padding: 12px;
        border-bottom: 1px solid #e9f0f6;
    }

    .chat-user:hover {
        background: #eef6ff;
    }

    .chat-user.active {
        background: #dff0ff;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        background: #cfe2f3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #174a75;
    }

    .chat-right {
        display: flex;
        flex-direction: column;
        min-height: 72vh;
    }

    .chat-header {
        padding: 12px 14px;
        border-bottom: 1px solid #e9f0f6;
        background: #f8fbff;
    }

    .chat-body {
        flex: 1;
        overflow-y: auto;
        max-height: calc(72vh - 130px);
        padding: 14px;
        background: #fbfdff;
        scroll-behavior: smooth;
    }

    .chat-row {
        display: flex;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .chat-row.mine {
        justify-content: flex-end;
    }

    .chat-bubble {
        max-width: 72%;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 14px;
        line-height: 1.4;
    }

    .chat-bubble.other {
        background: #ffffff;
        border: 1px solid #d8e5f2;
    }

    .chat-bubble.mine {
        background: #0d6efd;
        color: #fff;
    }

    .chat-time {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        opacity: .8;
    }

    .chat-bulk-toolbar {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .chat-bulk-btn {
        border: 1px solid #c8d7e6;
        background: #fff;
        color: #35506a;
        border-radius: 6px;
        font-size: 12px;
        line-height: 1;
        padding: 7px 10px;
        cursor: pointer;
    }

    .chat-bulk-btn:hover {
        background: #f1f6fb;
    }

    .chat-bulk-btn.danger {
        border-color: #f0b7b7;
        color: #9c2f2f;
        background: #fff8f8;
    }

    .chat-bulk-btn.danger:hover {
        background: #ffecec;
    }

    .chat-row.selected .chat-bubble {
        outline: 2px solid #86b6ff;
        box-shadow: 0 0 0 1px #86b6ff;
    }

    .chat-footer {
        border-top: 1px solid #e9f0f6;
        padding: 12px;
        background: #fff;
    }

    .chat-attach-info {
        min-height: 16px;
        font-size: 12px;
        color: #607387;
    }

    .unread-dot {
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        background: #0d6efd;
        color: #fff;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
    }
</style>
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <h3 class="fw-bold mb-1">Dealer Chat</h3>
        <p class="text-muted mb-0">Admin and Super Admin can chat with dealer users.</p>
    </div>
</div>

<div class="chat-wrap row g-0">
    <div class="col-lg-4 chat-left">
        <div class="p-3 border-bottom">
            <h5 class="mb-0">Provider List</h5>
            <small class="text-muted">Home Barber & Salon Users</small>
        </div>
        <div id="dealerList" style="max-height:72vh;overflow:auto;"></div>
    </div>

    <div class="col-lg-8 chat-right">
        <div class="chat-header">
            <div class="fw-semibold" id="chatUserName">Select a provider</div>
            <small class="text-muted" id="chatUserEmail">No conversation selected</small>
            <div class="chat-bulk-toolbar">
                <button type="button" class="chat-bulk-btn" id="btnSelectAll" disabled>Select All</button>
                <button type="button" class="chat-bulk-btn" id="btnClearSelected" disabled>Clear</button>
                <button type="button" class="chat-bulk-btn danger" id="btnDeleteSelected" disabled>Delete Selected</button>
                <small class="text-muted" id="selectedCountText">Selected: 0</small>
                <small class="text-muted">Click message to select</small>
            </div>
        </div>

        <div class="chat-body" id="chatMessages">
            <div class="text-muted">Select a provider from left side.</div>
        </div>

        <div class="chat-footer">
            <form id="chatSendForm" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                <input type="hidden" id="receiverId" name="receiver_id">
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" id="chatMessage" name="message" placeholder="Type message..." disabled>
                    <input type="file" id="chatImage" name="image" accept="image/*" class="d-none" disabled>
                    <button type="button" class="btn btn-outline-primary" id="btnImage" disabled>Image</button>
                    <button type="submit" class="btn btn-primary" id="btnSend" disabled>Send</button>
                </div>
                <div id="chatAttachInfo" class="chat-attach-info"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.min.js"></script>
<script>
    $(function () {
        const authId = {{ (int) auth()->id() }};
        const users = @json($users ?? []);
        const unreadCounts = @json($unreadCounts ?? []);

        let selectedDealer = null;
        let selectedConversationId = null;
        let echo = null;
        let activeChannel = null;
        let selectedChatIds = [];
        const bulkDeleteUrl = '#'; // Route not defined

        function scrollMessagesToBottom() {
            const box = $('#chatMessages');
            if (!box.length) {
                return;
            }

            const target = box[0].scrollHeight;
            box.stop().animate({ scrollTop: target }, 180);
        }

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function formatTime(ts) {
            if (!ts) return '';
            const d = new Date(ts.replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return ts;
            return d.toLocaleString();
        }

        function renderDealerList() {
            if (!users.length) {
                $('#dealerList').html('<div class="p-3 text-muted">No provider users found.</div>');
                return;
            }

            // Sort users by their last message timestamp or presence of unread messages
            const sortedUsers = [...users].sort((a, b) => {
                const countA = unreadCounts[a.id] || 0;
                const countB = unreadCounts[b.id] || 0;
                if (countB !== countA) return countB - countA;
                return 0; // Further sorting could be done by timestamp if available
            });

            const html = sortedUsers.map(function (u) {
                const initial = (u.name || 'D').trim().charAt(0).toUpperCase();
                const avatar = u.profile_image
                    ? '<img src="' + '{{ asset('uploads/profile/') }}/' + u.profile_image + '" class="chat-avatar" alt="avatar">'
                    : '<span class="chat-avatar">' + initial + '</span>';
                const unread = unreadCounts[u.id] ? '<span class="unread-dot">' + unreadCounts[u.id] + '</span>' : '';

                return '' +
                    '<div class="chat-user" data-id="' + u.id + '" data-name="' + escapeHtml(u.name) + '" data-email="' + escapeHtml(u.email || '') + '">' +
                        '<div class="d-flex align-items-center gap-2">' +
                            avatar +
                            '<div class="flex-grow-1 overflow-hidden">' +
                                '<div class="fw-semibold text-truncate">' + escapeHtml(u.name) + '</div>' +
                                '<small class="text-muted text-truncate d-block">' + escapeHtml(u.email || '') + '</small>' +
                            '</div>' +
                            unread +
                        '</div>' +
                    '</div>';
            }).join('');

            $('#dealerList').html(html);
        }

        function renderMessages(items) {
            selectedChatIds = [];

            if (!items.length) {
                $('#chatMessages').html('<div class="text-muted">No messages yet.</div>');
                updateSelectionState();
                return;
            }

            const html = items.map(function (m) {
                const mine = Number(m.sender_id) === Number(authId);
                const rowClass = mine ? 'mine' : '';
                const bubbleClass = mine ? 'mine' : 'other';

                let media = '';
                if (m.image_url) {
                    media += '<div class="mt-2"><img src="' + m.image_url + '" style="max-width:220px;border-radius:8px;"></div>';
                }

                return '' +
                    '<div class="chat-row ' + rowClass + '" data-chat-id="' + m.id + '">' +
                        '<div class="chat-bubble ' + bubbleClass + '">' +
                            '<div>' + escapeHtml(m.message || '') + '</div>' +
                            media +
                            '<span class="chat-time">' + formatTime(m.created_at || m.time) + '</span>' +
                        '</div>' +
                    '</div>';
            }).join('');

            $('#chatMessages').html(html);
            updateSelectionState();
            scrollMessagesToBottom();
        }

        function updateSelectionState() {
            const hasMessages = $('#chatMessages .chat-row[data-chat-id]').length > 0;
            const selectedCount = selectedChatIds.length;

            $('#selectedCountText').text('Selected: ' + selectedCount);
            $('#btnSelectAll').prop('disabled', !hasMessages);
            $('#btnClearSelected').prop('disabled', selectedCount === 0);
            $('#btnDeleteSelected').prop('disabled', selectedCount === 0);
        }

        function clearSelection() {
            selectedChatIds = [];
            $('#chatMessages .chat-row').removeClass('selected');
            updateSelectionState();
        }

        function updateAttachInfo() {
            const img = $('#chatImage')[0].files[0];
            const parts = [];
            if (img) parts.push('Image: ' + img.name);
            $('#chatAttachInfo').text(parts.join(' | '));
        }

        function resetAttachInfo() {
            $('#chatImage').val('');
            $('#chatAttachInfo').text('');
        }

        function markRead(conversationId) {
            if (!conversationId) return;
            $.get('{{ url('admin/chat/mark/read/admin') }}/' + conversationId, function(res) {
                if (res.status) {
                    // Reset unread count for the active user locally
                    if (selectedDealer) {
                        unreadCounts[selectedDealer.id] = 0;
                        renderDealerList();
                    }
                }
            });
        }

        function fetchConversation(receiverId) {
            $.get('{{ url('admin/chat/fetch/admin') }}/' + receiverId, function (res) {
                if (res.status) {
                    renderMessages(res.chat || []);
                    if (res.conversation_id) {
                        selectedConversationId = res.conversation_id;
                        subscribeToConversation(selectedConversationId);
                        markRead(selectedConversationId);
                    }
                }
            });
        }

        function initReverb() {
            const EchoCtor = window.Echo;
            if (!EchoCtor) {
                console.error('Laravel Echo not found');
                return;
            }

            echo = new EchoCtor({
                broadcaster: 'reverb',
                key: '{{ env('VITE_REVERB_APP_KEY') }}',
                wsHost: '{{ env('VITE_REVERB_HOST') }}',
                wsPort: {{ env('VITE_REVERB_PORT', 8080) }},
                wssPort: {{ env('VITE_REVERB_PORT', 8080) }},
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '{{ url('/broadcasting/auth') }}',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }
            });

            // Global listen for any message sent to admin role
            echo.private('App.Models.User.' + authId)
                .listen('.ChatEvent', function (event) {
                    console.log('Global notification received:', event);
                    const senderId = event.message.sender_id;

                    // Update global count
                    unreadCounts[senderId] = (unreadCounts[senderId] || 0) + 1;

                    // Re-render list to move sender to top
                    renderDealerList();
                });

            echo.connector.pusher.connection.bind('state_change', function(states) {
                console.log('Reverb connection state:', states.current);
            });
        }

        function subscribeToConversation(conversationId) {
            if (!echo || !conversationId) {
                return;
            }

            if (activeChannel) {
                echo.leave(activeChannel);
            }

            activeChannel = 'chat-conversation.' + conversationId;

            echo.private('chat-conversation.' + conversationId)
                .listen('.ChatEvent', function (event) {
                    console.log('Real-time event received:', event);
                    if (!selectedDealer || !event || !event.message) {
                        return;
                    }

                    if (Number(event.message.sender_id) === Number(authId)) {
                        return;
                    }

                    // Increment unread count for the sender and re-render list to move them to top
                    const senderId = event.message.sender_id;
                    unreadCounts[senderId] = (unreadCounts[senderId] || 0) + 1;
                    renderDealerList();

                    if (selectedDealer && Number(selectedDealer.id) === Number(senderId)) {
                        fetchConversation(selectedDealer.id);
                    }
                });
        }

        $('#dealerList').on('click', '.chat-user', function () {
            $('.chat-user').removeClass('active');
            $(this).addClass('active');

            selectedDealer = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                email: $(this).data('email')
            };

            $('#receiverId').val(selectedDealer.id);
            $('#chatUserName').text(selectedDealer.name);
            $('#chatUserEmail').text(selectedDealer.email || '');
            $('#chatMessage, #btnSend, #btnImage, #chatImage').prop('disabled', false);
            resetAttachInfo();
            fetchConversation(selectedDealer.id);
        });

        $('#btnImage').on('click', function () { $('#chatImage').trigger('click'); });
        $('#chatImage').on('change', updateAttachInfo);

        $('#chatSendForm').on('submit', function (e) {
            e.preventDefault();
            if (!selectedDealer) return;

            const msg = ($('#chatMessage').val() || '').trim();
            const img = $('#chatImage')[0].files[0];
            if (!msg && !img) return;

            const formData = new FormData(this);
            $('#btnSend').prop('disabled', true);

            $.ajax({
                url: '{{ route('admin.chat.send') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res.status) {
                        $('#chatMessage').val('');
                        resetAttachInfo();
                        fetchConversation(selectedDealer.id);
                    }
                },
                complete: function () {
                    $('#btnSend').prop('disabled', false);
                }
            });
        });

        $('#chatMessages').on('click', '.chat-row', function (e) {
            if ($(e.target).is('img')) {
                return;
            }

            const chatId = Number($(this).data('chat-id'));
            if (!chatId) {
                return;
            }

            const row = $(this);
            if (!row.hasClass('selected')) {
                if (!selectedChatIds.includes(chatId)) {
                    selectedChatIds.push(chatId);
                }
                row.addClass('selected');
            } else {
                selectedChatIds = selectedChatIds.filter(function (id) {
                    return id !== chatId;
                });
                row.removeClass('selected');
            }

            updateSelectionState();
        });

        $('#btnSelectAll').on('click', function () {
            const ids = [];
            $('#chatMessages .chat-row[data-chat-id]').each(function () {
                const id = Number($(this).data('chat-id'));
                if (id) {
                    ids.push(id);
                    $(this).addClass('selected');
                }
            });
            selectedChatIds = ids;
            updateSelectionState();
        });

        $('#btnClearSelected').on('click', function () {
            clearSelection();
        });

        $('#btnDeleteSelected').on('click', function () {
            if (!selectedDealer || selectedChatIds.length === 0) {
                return;
            }

            $.ajax({
                url: bulkDeleteUrl,
                method: 'DELETE',
                data: {
                    chat_ids: selectedChatIds
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res.status) {
                        clearSelection();
                        fetchConversation(selectedDealer.id);
                    }
                }
            });
        });

        initReverb(); // Final init call
        renderDealerList();
    });
</script>
@endpush
