jQuery(document).ready(function($) {
    
    console.log('PSE Script Loaded');
    
    // Like button handler
    $(document).on('click', '.pse-like-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: pse_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pse_handle_like',
                post_id: postId,
                nonce: pse_ajax.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false);
                
                if (response.success) {
                    var $count = $btn.find('.pse-count');
                    $count.text(response.data.count);
                    
                    if (response.data.action === 'liked') {
                        $btn.addClass('pse-liked');
                        $btn.find('.pse-text').text('Liked');
                        showMsg('Post liked!', 'success');
                    } else {
                        $btn.removeClass('pse-liked');
                        $btn.find('.pse-text').text('Like');
                        showMsg('Post unliked', 'info');
                    }
                } else {
                    showMsg('Error: ' + (response.data.message || 'Something went wrong'), 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                showMsg('Connection error. Please try again.', 'error');
            }
        });
    });
    
    // Comment button handler
    $(document).on('click', '.pse-comment-btn', function(e) {
        e.preventDefault();
        
        var postId = $(this).data('post-id');
        var $commentsArea = $('.pse-comments-area[data-post-id="' + postId + '"]');
        
        if ($commentsArea.is(':visible')) {
            $commentsArea.slideUp();
        } else {
            $('.pse-comments-area').slideUp();
            $commentsArea.slideDown();
            loadComments(postId);
        }
    });
    
    // Submit comment
    $(document).on('click', '.pse-submit-comment', function() {
        var $area = $(this).closest('.pse-comments-area');
        var postId = $area.data('post-id');
        var $textarea = $area.find('textarea');
        var comment = $textarea.val().trim();
        
        if (!comment) {
            showMsg('Please enter a comment', 'error');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Posting...');
        
        $.ajax({
            url: pse_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pse_add_comment',
                post_id: postId,
                comment: comment,
                nonce: pse_ajax.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Post Comment');
                
                if (response.success) {
                    $textarea.val('');
                    showMsg(response.data.message, 'success');
                    loadComments(postId);
                    $('.pse-comment-btn[data-post-id="' + postId + '"] .pse-count').text(response.data.count);
                } else {
                    showMsg(response.data.message || 'Failed to add comment', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Post Comment');
                showMsg('Connection error', 'error');
            }
        });
    });
    
    // Share button handler - FIXED VERSION
    $(document).on('click', '.pse-share-btn', function(e) {
        e.preventDefault();
        
        // Get current page URL and title
        var currentUrl = window.location.href;
        var pageTitle = document.title;
        var encodedUrl = encodeURIComponent(currentUrl);
        var encodedTitle = encodeURIComponent(pageTitle);
        
        // Create and show modal
        var $modal = $('.pse-share-modal');
        $modal.show();
        
        // Facebook Share - FIXED
        $('.pse-share-fb').off('click').on('click', function() {
            var fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl + '&t=' + encodedTitle;
            window.open(fbUrl, '_blank', 'width=600,height=400,location=yes,left=' + (screen.width/2 - 300) + ',top=' + (screen.height/2 - 200));
            $modal.hide();
            return false;
        });
        
        // Twitter Share - FIXED
        $('.pse-share-tw').off('click').on('click', function() {
            var twitterUrl = 'https://twitter.com/intent/tweet?text=' + encodedTitle + '&url=' + encodedUrl;
            window.open(twitterUrl, '_blank', 'width=600,height=400,location=yes,left=' + (screen.width/2 - 300) + ',top=' + (screen.height/2 - 200));
            $modal.hide();
            return false;
        });
        
        // LinkedIn Share - FIXED
        $('.pse-share-li').off('click').on('click', function() {
            var linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl;
            window.open(linkedinUrl, '_blank', 'width=600,height=400,location=yes,left=' + (screen.width/2 - 300) + ',top=' + (screen.height/2 - 200));
            $modal.hide();
            return false;
        });
        
        // WhatsApp Share - Already working
        $('.pse-share-wa').off('click').on('click', function() {
            var whatsappUrl = 'https://wa.me/?text=' + encodedTitle + '%20' + encodedUrl;
            window.open(whatsappUrl, '_blank');
            $modal.hide();
            return false;
        });
    });
    
    // Close modal
    $(document).on('click', '.pse-modal-close', function() {
        $('.pse-share-modal').hide();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('pse-share-modal')) {
            $('.pse-share-modal').hide();
        }
    });
    
    // Load comments function
    function loadComments(postId) {
        var $list = $('.pse-comments-area[data-post-id="' + postId + '"] .pse-comments-list');
        $list.html('<div class="pse-loading">Loading comments...</div>');
        
        $.ajax({
            url: pse_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pse_load_comments',
                post_id: postId,
                nonce: pse_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $list.html(response.data.html);
                } else {
                    $list.html('<div class="pse-error">Error loading comments</div>');
                }
            },
            error: function() {
                $list.html('<div class="pse-error">Error loading comments</div>');
            }
        });
    }
    
    // Show message function
    function showMsg(msg, type) {
        var $msg = $('<div class="pse-msg pse-msg-' + type + '">' + msg + '</div>');
        $('body').append($msg);
        $msg.fadeIn(300);
        
        setTimeout(function() {
            $msg.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});