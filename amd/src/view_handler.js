define(['jquery', 'core/ajax'], function($, Ajax) {
    "use strict";

    return {
        init: function(params) {
            
            // Auto-save functionality (silent, no visual feedback)
            var autoSaveTimer = null;
            var isSaving = false;
            var formUrl = params.formUrl;

            function autoSaveProgress() {
                if (isSaving) return;
                
                isSaving = true;
                var formData = new FormData(document.getElementById('learningStyleForm'));
                formData.set('action', 'autosave');
                
                fetch(formUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    // Silent save - no visual feedback
                    isSaving = false;
                })
                .catch(function(error) {
                    // console.error('Auto-save error:', error);
                    isSaving = false;
                });
            }

            // Listen to changes in selects for auto-save
            $('.select-q').on('change', function() {
                // Remove red highlight when user answers the question
                var select = $(this);
                var listItem = select.closest('.learning_style_item');
                
                // Remove error styling if present
                if (listItem.hasClass('question-error-highlight')) {
                    listItem.css({
                        'border': '',
                        'background-color': '',
                        'border-radius': '',
                        'padding': '',
                        'margin-bottom': '',
                        'box-shadow': ''
                    });
                    listItem.removeClass('question-error-highlight');
                }
                
                // Clear previous timer
                if (autoSaveTimer) {
                    clearTimeout(autoSaveTimer);
                }
                
                // Auto-save after 2 seconds of inactivity
                autoSaveTimer = setTimeout(autoSaveProgress, 2000);
            });

            // Handle form submission for navigation
            $('#learningStyleForm').on('submit', function(e) {
                // Determine which button triggered the submit
                // Note: e.originalEvent.submitter is supported in modern browsers
                var submitter = e.originalEvent.submitter;
                var action = submitter ? submitter.value : 'next';
                
                // For "previous" button, always allow navigation without validation
                if (action === 'previous') {
                    return true;
                }
                
                // Only validate for "next" and "finish" actions
                if (action !== 'next' && action !== 'finish') {
                    return true;
                }

                // Validate current page for next/finish
                var selectsOnPage = $('.select-q');
                var allAnswered = true;
                var firstUnanswered = null;

                selectsOnPage.each(function() {
                    if ($(this).val() === '') {
                        allAnswered = false;
                        var listItem = $(this).closest('.learning_style_item');

                        if (listItem.length) {
                            listItem.css({
                                'border': '3px solid #d32f2f',
                                'background-color': '#ffebee',
                                'border-radius': '10px',
                                'padding': '24px 28px',
                                'margin-bottom': '1.5rem',
                                'box-shadow': '0 4px 8px rgba(211, 47, 47, 0.3)'
                            });
                            listItem.addClass('question-error-highlight');

                            if (!firstUnanswered) {
                                firstUnanswered = listItem;
                            }
                        }
                    }
                });

                if (!allAnswered) {
                    e.preventDefault();

                    // Scroll to first unanswered question (card)
                    if (firstUnanswered) {
                        firstUnanswered[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }

                    return false;
                }
            });

            // Auto-scroll to first unanswered question when continuing test
            if (params.shouldAutoScroll) {
                // Wait a bit for the page to fully render
                setTimeout(function() {
                    // Find first unanswered question on current page
                    var found = false;
                    $('.select-q').each(function() {
                        if (found) return; 
                        
                        if ($(this).val() === '') {
                            var listItem = $(this).closest('.learning_style_item');
                            
                            // Add green highlight to the entire card
                            if (listItem.length) {
                                listItem.css({
                                    'border': '3px solid #28a745',
                                    'background-color': '#d4edda',
                                    'border-radius': '10px',
                                    'padding': '24px 28px',
                                    'margin-bottom': '1.5rem',
                                    'box-shadow': '0 4px 8px rgba(40, 167, 69, 0.3)',
                                    'transition': 'all 0.3s ease'
                                });
                                
                                // Scroll to it
                                listItem[0].scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                                
                                // Remove highlight after 5 seconds
                                setTimeout(function() {
                                    listItem.css({
                                        'border': '',
                                        'background-color': '',
                                        'border-radius': '',
                                        'padding': '',
                                        'margin-bottom': '',
                                        'box-shadow': ''
                                    });
                                }, 5000);
                            }
                            found = true;
                        }
                    });
                }, 300);
            }

            // Scroll to finish button when coming from block with all questions answered
            if (params.scrollToFinish) {
                setTimeout(function() {
                    var finishBtn = $('#submitBtn');
                    if (finishBtn.length) {
                        finishBtn[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        
                        // Add green pulsing highlight to the button
                        finishBtn.css({
                            'box-shadow': '0 0 20px rgba(40, 167, 69, 0.8)',
                            'transition': 'all 0.3s ease'
                        });
                        
                        // Remove highlight after 5 seconds
                        setTimeout(function() {
                            finishBtn.css('box-shadow', '');
                        }, 5000);
                    }
                }, 300);
            }
        }
    };
});
