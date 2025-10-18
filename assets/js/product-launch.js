/* PL per-file guard */
if (typeof window.__PL_FILE_GUARDS === 'undefined') { window.__PL_FILE_GUARDS = {}; }
if (window.__PL_FILE_GUARDS['assets/js/product-launch.js']) { console.warn('Duplicate JS skipped:', 'assets/js/product-launch.js'); }
else { window.__PL_FILE_GUARDS['assets/js/product-launch.js'] = 1;

/*
 * Product Launch Plugin - Complete Fixed JavaScript
 * Version 2.3.52 - Preview Generation Fix
 * 
 * FIXES APPLIED:
 * - Bug #1: Modal z-index conflict (modal behind chat)
 * - Bug #2: Override existing fields option
 * - Bug #3: Restored "Apply to Form" button
 * - Bug #4: AI Assist button styling
 * - Bug #5: Generate actual content preview before showing selective modal
 */

class EnhancedProductLaunchCoach {
    constructor() {
        this.currentPhase = '';
        this.conversationHistory = [];
        this.formContext = new Map();
        this.isLoading = false;
        this.apiRetryCount = 0;
        this.maxRetries = 3;
        this.currentAnalysis = null;
        this.pendingRequest = null;
        this.autoSaveTimeout = null;
        this.lastActionWasAnalysis = false;
        this.pendingGeneratedContent = null; // NEW: Store pre-generated content
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeComponents();
        this.scanFormFields();
        this.loadPreviousContext();
    }

    /**
     * Format AI response markers to HTML
     * Converts ⟦b⟧ to <strong>, ⟦i⟧ to <em>, etc.
     */
    formatAIResponse(text) {
        if (!text || typeof text !== 'string') return text;

        return text
            .replace(/⟦b⟧/g, '<strong>')
            .replace(/⟦\/b⟧/g, '</strong>')
            .replace(/⟦i⟧/g, '<em>')
            .replace(/⟦\/i⟧/g, '</em>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
    }
    
    bindEvents() {
        // AI Coaching session start
        jQuery(document).on('click', '.start-ai-coaching', (e) => {
            e.preventDefault();
            this.startCoachingSession(jQuery(e.currentTarget).data('phase'));
        });
        
        // Modal controls
        jQuery(document).on('click', '.modal-close', (e) => {
            e.preventDefault();
            this.closeModal();
        });
        
        // Chat form submission
        jQuery(document).on('submit', '.chat-form', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Enter key in textarea
        jQuery(document).on('keypress', '.chat-textarea', (e) => {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // AI field assistance
        jQuery(document).on('click', '.ai-assist-btn', (e) => {
            e.preventDefault();
            const fieldId = jQuery(e.currentTarget).data('field');
            this.requestFieldAssistance(fieldId);
        });
        
        // Form field monitoring
        jQuery(document).on('input change', '.ai-fillable', (e) => {
            this.updateFormContext();
            this.autoSaveProgress();
        });
        
        // Quick actions
        jQuery(document).on('click', '.quick-action-btn', (e) => {
            e.preventDefault();
            const action = jQuery(e.currentTarget).data('action');
            this.handleQuickAction(action);
        });
        
        // Apply to Form button
        jQuery(document).on('click', '.apply-to-form-btn', (e) => {
            e.preventDefault();
            const $message = jQuery(e.currentTarget).closest('.message');
            const messageText = $message.find('.message-text').text();
            this.applyMessageToForm(messageText);
        });
        
        // API Test
        jQuery(document).on('click', '.test-api-connection', (e) => {
            e.preventDefault();
            this.testAPIConnection();
        });
        
        // Close modal on backdrop
        jQuery(document).on('click', '#product-launch-modal', (e) => {
            if (e.target === e.currentTarget) {
                this.closeModal();
            }
        });
        
        // Escape key
        jQuery(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                jQuery('.override-confirmation-modal').remove();
            }
        });
    }
    
    scanFormFields() {
        this.formContext.clear();
        
        jQuery('.ai-fillable').each((index, element) => {
            const $field = jQuery(element);
            const fieldId = $field.attr('id') || $field.attr('name');
            const fieldLabel = $field.data('field') || this.generateFieldLabel($field);
            const fieldType = $field.prop('tagName').toLowerCase();
            
            if (!fieldId) return;
            
            if (!$field.siblings('.ai-assist-btn').length && !$field.parent().find('.ai-assist-btn').length) {
                const assistBtn = this.createAssistButton(fieldId);
                $field.after(assistBtn);
            }
            
            this.formContext.set(fieldId, {
                element: $field,
                label: fieldLabel,
                type: fieldType,
                value: $field.val() || '',
                placeholder: $field.attr('placeholder') || ''
            });
        });
        
        this.updateFormContext();
    }
    
    generateFieldLabel($field) {
        const id = $field.attr('id');
        if (id) {
            const $label = jQuery(`label[for="${id}"]`);
            if ($label.length) return $label.text().trim();
        }
        
        const $parentLabel = $field.closest('label');
        if ($parentLabel.length) return $parentLabel.text().trim();
        
        const $thScope = $field.closest('tr').find('th[scope="row"] label');
        if ($thScope.length) return $thScope.text().trim();
        
        return $field.attr('name') || 'Unknown Field';
    }
    
    createAssistButton(fieldId) {
        const assistBtn = jQuery('<button type="button" class="ai-assist-btn"></button>')
            .attr('data-field', fieldId)
            .attr('title', 'Get AI assistance for this field')
            .html('<span class="dashicons dashicons-businesswoman"></span><span class="ai-assist-text">AI Assist</span>');
        
        return assistBtn;
    }
    
    requestFieldAssistance(fieldId) {
        const fieldInfo = this.formContext.get(fieldId);
        if (!fieldInfo) return;
        
        this.showFieldLoading(fieldId);
        
        const context = this.gatherFormContext();
        this.callAIForField(fieldId, context);
    }
    
    gatherFormContext() {
        const context = {};
        this.formContext.forEach((fieldInfo, fieldId) => {
            const currentValue = fieldInfo.element.val();
            if (currentValue && currentValue.trim()) {
                context[fieldId] = currentValue.trim();
            }
        });
        return context;
    }
    
    updateFormContext() {
        this.formContext.forEach((fieldInfo, fieldId) => {
            fieldInfo.value = fieldInfo.element.val() || '';
        });
    }
    
    showFieldLoading(fieldId) {
        const $btn = jQuery(`[data-field="${fieldId}"]`);
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update spin"></span> <span class="ai-assist-text">AI Writing...</span>');
        
        const fieldInfo = this.formContext.get(fieldId);
        if (fieldInfo) {
            fieldInfo.element.addClass('ai-loading');
        }
    }
    
    hideFieldLoading(fieldId) {
        const $btn = jQuery(`[data-field="${fieldId}"]`);
        $btn.prop('disabled', false)
            .html('<span class="dashicons dashicons-businesswoman"></span> <span class="ai-assist-text">AI Assist</span>');
        
        const fieldInfo = this.formContext.get(fieldId);
        if (fieldInfo) {
            fieldInfo.element.removeClass('ai-loading');
        }
    }
    
    callAIForField(fieldId, context) {
        jQuery.ajax({
            url: productLaunch.ajax_url,
            type: 'POST',
            data: {
                action: 'product_launch_field_assist',
                phase: this.getCurrentPhase(),
                field_id: fieldId,
                context: JSON.stringify(context),
                field_label: this.formContext.get(fieldId)?.label || fieldId,
                nonce: productLaunch.nonce
            },
            timeout: 30000,
            success: (response) => {
                this.hideFieldLoading(fieldId);
                
                if (response.success) {
                    this.fillField(fieldId, response.data);
                } else {
                    this.showNotification('Failed to get AI assistance: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: (xhr, status, error) => {
                this.hideFieldLoading(fieldId);
                let errorMsg = 'Network error while getting AI assistance';
                
                if (xhr.status === 429) {
                    errorMsg = 'Rate limit exceeded. Please wait before trying again.';
                } else if (xhr.status === 401) {
                    errorMsg = 'Authentication error. Please check your API settings.';
                }
                
                this.showNotification(errorMsg, 'error');
            }
        });
    }
    
    fillField(fieldId, content) {
        const fieldInfo = this.formContext.get(fieldId);
        if (!fieldInfo) return;
        
        const cleanContent = content.replace(/^["']|["']$/g, '').trim();
        
        fieldInfo.element
            .addClass('ai-filled')
            .val(cleanContent)
            .trigger('input')
            .trigger('change')
            .animate({
                backgroundColor: '#f0fdf4'
            }, 500, function() {
                jQuery(this).css('backgroundColor', '');
            });
        
        fieldInfo.value = cleanContent;
        
        if (fieldInfo.type === 'textarea') {
            this.autoResize(fieldInfo.element[0]);
        }
        
        this.immediateFormSave();
        this.showNotification(`AI content added to ${fieldInfo.label}!`, 'success', 3000);
    }
    
    immediateFormSave() {
        const phase = this.getCurrentPhase();
        const context = this.gatherFormContext();
        
        if (Object.keys(context).length > 0) {
            jQuery.ajax({
                url: productLaunch.ajax_url,
                type: 'POST',
                data: {
                    action: 'product_launch_save_progress',
                    phase: phase,
                    progress_data: JSON.stringify(context),
                    nonce: productLaunch.nonce
                },
                success: () => {
                    const storageKey = `pl_context_${phase}`;
                    localStorage.setItem(storageKey, JSON.stringify(context));
                },
                error: (xhr, status, error) => {
                    const storageKey = `pl_context_${phase}`;
                    localStorage.setItem(storageKey, JSON.stringify(context));
                }
            });
        }
    }
    
    getCurrentPhase() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        
        if (page && page.includes('product-launch-')) {
            return page.replace('product-launch-', '').replace('-', '_');
        }
        
        return this.currentPhase || 'general';
    }
    
    initializeComponents() {
        this.createModal();
        this.updateProgressTracker();
    }
    
    createModal() {
        if (jQuery('#product-launch-modal').length) {
            return;
        }
        
        const modalHtml = `
        <div id="product-launch-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <span class="dashicons dashicons-businesswoman"></span>
                        <span class="title-text">AI Product Launch Coach</span>
                        <span class="beta-badge">Beta</span>
                    </h2>
                    <button class="modal-close" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="chat-container">
                    <div class="chat-messages" role="log" aria-live="polite"></div>
                    <div class="chat-input">
                        <div class="quick-actions">
                            <button type="button" class="quick-action-btn" data-action="suggest-improvements" title="Get improvement suggestions">
                                <span class="dashicons dashicons-lightbulb"></span>
                                Analyze & Suggest Improvements
                            </button>
                            <button type="button" class="quick-action-btn" data-action="fill-missing" title="Help fill empty fields">
                                <span class="dashicons dashicons-edit"></span>
                                Fill Missing Fields
                            </button>
                        </div>
                        <form class="chat-form">
                            <div class="chat-input-wrapper">
                                <textarea class="chat-textarea" placeholder="Ask me anything about your product launch..." rows="1" aria-label="Chat message"></textarea>
                                <button type="submit" class="chat-send-btn" aria-label="Send message">
                                    <span class="dashicons dashicons-arrow-up-alt"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;
        
        jQuery('body').append(modalHtml);
        jQuery('#product-launch-modal .chat-container').append('<div class="plc-disclaimer" role="note" aria-live="polite">Coach Vito can make mistakes. Please double-check Info</div>');
    }
    
    startCoachingSession(phase) {
        this.currentPhase = phase;
        this.conversationHistory = [];
        
        const phaseNames = {
            'market_clarity': 'Getting Market Clarity',
            'create_offer': 'Creating Your Offer',
            'create_service': 'Creating Your Service',
            'build_funnel': 'Building Sales Funnel',
            'email_sequences': 'Writing Email Sequences',
            'organic_posts': 'Creating Organic Posts',
            'facebook_ads': 'Creating Facebook Ads',
            'launch': 'Launching Your Product'
        };
        
        const phaseName = phaseNames[phase] || 'Product Launch Coaching';
        jQuery('.modal-title .title-text').text(phaseName);
        
        jQuery('.chat-messages').empty();
        this.showModal();
        
        setTimeout(() => {
            this.loadContextualGreeting(phase);
        }, 500);
    }
    
    loadContextualGreeting(phase) {
        const loadingMessage = "Hello! Let me review your business context and prepare personalized guidance for this phase...";
        this.addMessageToChat('ai', loadingMessage);
        
        const formContext = this.gatherFormContext();
        
        jQuery.ajax({
            url: productLaunch.ajax_url,
            type: 'POST',
            data: {
                action: 'product_launch_chat',
                phase: phase,
                message: 'Based on our previous discussions and business context, provide a contextual greeting for this phase and offer to help fill the form fields.',
                history: JSON.stringify([]),
                context: JSON.stringify(formContext),
                nonce: productLaunch.nonce
            },
            timeout: 15000,
            success: (response) => {
                const lastMessage = jQuery('.chat-messages .message').last();
                if (lastMessage.length && lastMessage.find('.message-text').text().indexOf('Let me review your business context') > -1) {
                    lastMessage.find('.message-text').html(this.formatMessage(response.data));
                } else {
                    this.addMessageToChat('ai', response.data);
                }
            },
            error: () => {
                const lastMessage = jQuery('.chat-messages .message').last();
                if (lastMessage.length) {
                    lastMessage.find('.message-text').html(this.formatMessage(this.getGenericGreeting(phase)));
                }
            }
        });
    }
    
    getGenericGreeting(phase) {
        const phaseIntros = {
            'create_offer': "I'm here to help you create an irresistible offer. Let's work on structuring your offer and pricing strategy.",
            'create_service': "I'm here to help you design your service delivery. Let's create a premium service offering.",
            'build_funnel': "I'm here to help you build your sales funnel. Let's create a high-converting system.",
            'email_sequences': "I'm here to help you write compelling email sequences. Let's craft emails that convert.",
            'organic_posts': "I'm here to help you create engaging organic content. Let's build anticipation for your launch.",
            'facebook_ads': "I'm here to help you create profitable Facebook ads. Let's design campaigns that convert.",
            'launch': "I'm here to help you execute your launch strategy. Let's coordinate all elements for success."
        };
        
        return phaseIntros[phase] || "I'm here to help you with your product launch. How can I assist you?";
    }
    
    sendMessage() {
        if (this.isLoading) return;
        
        const $textarea = jQuery('.chat-textarea');
        const message = $textarea.val().trim();
        
        if (!message) return;
        
        this.addMessageToChat('user', message);
        $textarea.val('');
        this.autoResize($textarea[0]);
        
        this.showLoading();
        this.callAI(message);
    }
    
    callAI(message, retryCount = 0) {
        const context = this.gatherFormContext();
        
        jQuery.ajax({
            url: productLaunch.ajax_url,
            type: 'POST',
            data: {
                action: 'product_launch_chat',
                phase: this.getCurrentPhase(),
                message: message,
                history: JSON.stringify(this.conversationHistory.slice(0, -1)),
                context: JSON.stringify(context),
                nonce: productLaunch.nonce
            },
            timeout: 45000,
            success: (response) => {
                this.hideLoading();
                this.apiRetryCount = 0;
                
                if (response.success) {
                    const aiResponse = response.data;
                    
                    if (this.pendingRequest) {
                        const { action, skipChat } = this.pendingRequest;
                        
                        if (action === 'suggest-improvements' && skipChat) {
                            this.showImprovementModal(aiResponse);
                            this.conversationHistory.push({
                                role: 'user',
                                content: this.pendingRequest.message,
                                timestamp: Date.now()
                            });
                            this.conversationHistory.push({
                                role: 'assistant',
                                content: aiResponse,
                                timestamp: Date.now()
                            });
                            this.lastActionWasAnalysis = true;
                            
                        } else if (action === 'fill-from-analysis') {
                            this.addMessageToChat('ai', aiResponse);
                            const emptyFields = this.pendingRequest.emptyFields;
                            this.fillFieldsSequentially(emptyFields, 0);
                            this.lastActionWasAnalysis = false;
                            
                        } else if (action === 'fill-missing') {
                            this.addMessageToChat('ai', aiResponse);
                            this.lastActionWasAnalysis = false;
                        }
                        
                        this.pendingRequest = null;
                        
                    } else {
                        this.lastActionWasAnalysis = false;
                        this.addMessageToChat('ai', aiResponse);
                    }
                } else {
                    this.handleAPIError(response.data || 'Failed to get AI response', message, retryCount);
                }
            },
            error: (xhr, status, error) => {
                this.hideLoading();
                this.handleAPIError(this.getErrorMessage(xhr, status, error), message, retryCount);
            }
        });
    }
    
    handleAPIError(errorMessage, originalMessage, retryCount) {
        if (retryCount < this.maxRetries && !errorMessage.includes('Rate limit')) {
            const delay = Math.pow(2, retryCount) * 1000;
            setTimeout(() => {
                this.showLoading();
                this.callAI(originalMessage, retryCount + 1);
            }, delay);
            return;
        }
        
        let userMessage = '';
        if (errorMessage.includes('Rate limit')) {
            userMessage = "I'm getting a lot of requests right now. Please wait a moment before trying again.";
        } else if (errorMessage.includes('API key')) {
            userMessage = "There seems to be an issue with the AI configuration. Please contact support.";
        } else {
            userMessage = `I'm having trouble responding right now. Please try again in a moment.`;
        }
        
        this.addMessageToChat('ai', userMessage);
    }
    
    getErrorMessage(xhr, status, error) {
        if (xhr.status === 429) return 'Rate limit exceeded';
        if (xhr.status === 401) return 'API key authentication failed';
        if (xhr.status === 403) return 'API access forbidden';
        if (status === 'timeout') return 'Request timeout';
        return `Network error: ${error}`;
    }
    
    addMessageToChat(sender, message) {
        const chatMessages = jQuery('.chat-messages');
        const messageClass = sender === 'user' ? 'user-message' : 'ai-message';
        const timestamp = this.getCurrentTime();
        
        const showApplyButton = sender === 'ai' && !this.lastActionWasAnalysis;
        
        const messageHtml = `
            <div class="message ${messageClass}">
                <div class="message-content">
                    <div class="message-text">${this.formatMessage(message)}</div>
                    <span class="message-time">${timestamp}</span>
                </div>
                ${showApplyButton ? this.getMessageActions() : ''}
            </div>
        `;
        
        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
        
        this.conversationHistory.push({
            role: sender === 'user' ? 'user' : 'assistant',
            content: message,
            timestamp: Date.now()
        });
        
        if (this.conversationHistory.length > 50) {
            this.conversationHistory = this.conversationHistory.slice(-40);
        }
    }
    
    getMessageActions() {
        return `
            <div class="message-actions">
                <button class="apply-to-form-btn" title="Apply suggestions to form">
                    <span class="dashicons dashicons-admin-page"></span>
                    Apply to Form
                </button>
            </div>
        `;
    }
    
    formatMessage(message) {
        // First, format AI response markers
        message = this.formatAIResponse(message);

        // Then apply additional formatting
        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^(.*)$/gm, function(match) {
                // Don't wrap if already wrapped
                if (match.startsWith('<') || match === '') return match;
                return '<p>' + match + '</p>';
            })
            .replace(/(<p><\/p>)/g, '')
            .replace(/(\d+\.)\s/g, '<br>$1 ');
    }
    
    handleQuickAction(action) {
        const context = this.gatherFormContext();
        let message = '';
        let skipChat = false;
        
        switch(action) {
            case 'suggest-improvements':
                if (Object.keys(context).length === 0) {
                    this.showNotification("Please fill in some form fields first before requesting analysis.", 'warning');
                    return;
                }
                
                const contextSummary = Object.entries(context)
                    .map(([key, value]) => {
                        const fieldInfo = this.formContext.get(key);
                        const label = fieldInfo ? fieldInfo.label : this.humanizeFieldName(key);
                        const preview = value.substring(0, 150);
                        return `**${label}:** ${preview}${value.length > 150 ? '...' : ''}`;
                    })
                    .join('\n\n');
                
                message = `Please analyze my current content and provide specific, actionable improvements. Structure your response with clear sections for what's working well and concrete suggestions:\n\n${contextSummary}`;
                skipChat = true;
                this.lastActionWasAnalysis = true;
                break;
                
            case 'fill-missing':
                const emptyFields = [];
                this.formContext.forEach((fieldInfo, fieldId) => {
                    if (!fieldInfo.element.val().trim()) {
                        emptyFields.push(fieldInfo.label);
                    }
                });
                
                if (emptyFields.length === 0) {
                    this.showNotification("All fields are already filled!", 'success');
                    return;
                }
                
                message = `Based on my existing content, please generate specific, ready-to-use content for these empty fields: ${emptyFields.join(', ')}. Provide actual field content, not suggestions.`;
                skipChat = false;
                this.lastActionWasAnalysis = false;
                break;
        }
        
        if (message) {
            if (!skipChat) {
                this.addMessageToChat('user', message);
            }
            
            this.showLoading();
            
            this.pendingRequest = {
                action: action,
                skipChat: skipChat,
                message: message
            };
            
            this.callAI(message);
        }
    }
    
    showImprovementModal(analysisContent) {
        console.log('[PL Coach] showImprovementModal called');

        // Remove any existing modal
        jQuery('.improvement-modal-overlay').remove();

        // Adjust chat modal z-index
        jQuery('#product-launch-modal').css('z-index', '99999');

        // Format the content
        const formattedContent = this.formatAnalysisContent(analysisContent);
        console.log('[PL Coach] Modal will show formatted content');

        // Create modal HTML with explicit structure
        const modalHtml = `
            <div class="improvement-modal-overlay">
                <div class="improvement-modal">
                    <div class="improvement-header">
                        <h3>
                            <span class="dashicons dashicons-lightbulb"></span> 
                            AI Analysis & Suggestions
                        </h3>
                        <button class="improvement-close" aria-label="Close">&times;</button>
                    </div>
                
                    <div class="improvement-content">
                        ${formattedContent}
                    </div>
                
                    <div class="improvement-actions">
                        <button class="button improvement-action-btn improvement-close-btn">
                            <span class="dashicons dashicons-no-alt"></span>
                            Close
                        </button>
                        <button class="button button-primary improvement-action-btn improvement-apply-btn">
                            <span class="dashicons dashicons-yes"></span>
                            Generate Improved Content
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Append to body
        jQuery('body').append(modalHtml);
        console.log('[PL Coach] Modal HTML appended');

        // Store current analysis
        this.currentAnalysis = analysisContent;

        // Bind close events
        jQuery('.improvement-close, .improvement-close-btn').on('click', () => {
            console.log('[PL Coach] Closing modal');
            jQuery('.improvement-modal-overlay').fadeOut(300, function() {
                jQuery(this).remove();
                jQuery('#product-launch-modal').css('z-index', '');
            });
        });

        // Bind apply button
        jQuery('.improvement-apply-btn').on('click', () => {
            console.log('[PL Coach] Generate Improved Content clicked');
            jQuery('.improvement-modal-overlay').fadeOut(300, function() {
                jQuery(this).remove();
                jQuery('#product-launch-modal').css('z-index', '');
            });

            this.requestImprovedContentFromAnalysis();
        });

        console.log('[PL Coach] Event handlers bound');
    }
    
    formatAnalysisContent(content) {
        console.log('[PL Coach] formatAnalysisContent called with:', content.substring(0, 100));

        // Format AI response markers first
        content = this.formatAIResponse(content);

        // Fix headers that run into numbered lists by ensuring a newline between them
        content = content.replace(/(###?\s+[^\n]+?)(\d+\.)/g, '$1\n$2');

        // Encourage clearer separation before numbered items
        content = content.replace(/(^|\n)(\d+)\.\s+/g, (match, prefix, number) => `${prefix}\n${number}. `);

        const formatListText = (value) => value
            .replace(/\n{2,}/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .trim();

        let formatted = content
            // Headers with better spacing - ensure they're followed by newlines
            .replace(/###\s+(.+?)(\n|$)/g, '<h3 class="analysis-h3" style="margin-top: 24px; margin-bottom: 12px; font-size: 18px; color: #1f2937; font-weight: 700;">$1</h3>\n')
            .replace(/##\s+(.+?)(\n|$)/g, '<h4 class="analysis-h4" style="margin-top: 20px; margin-bottom: 10px; font-size: 16px; color: #374151; font-weight: 600;">$1</h4>\n')

            // Bold and italic text enhancements
            .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #1f2937; font-weight: 600;">$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')

            // Numbered lists with better spacing and structure
            .replace(/(\d+)\.\s+([\s\S]*?)(?=(\n\d+\.\s)|$)/g, (match, number, text) => {
                const trimmedText = text.trim();
                if (!trimmedText) {
                    return match;
                }

                let title = '';
                let description = trimmedText;

                const leadingSplit = trimmedText.match(/^([^:\-\n]{1,120}?)(?:\s*[:\-–—]\s+)([\s\S]*)$/);
                if (leadingSplit && leadingSplit[2]) {
                    title = leadingSplit[1].trim();
                    description = leadingSplit[2].trim();
                } else {
                    const newlineIndex = trimmedText.indexOf('\n');
                    if (newlineIndex > 0 && newlineIndex < 160) {
                        title = trimmedText.slice(0, newlineIndex).trim();
                        description = trimmedText.slice(newlineIndex + 1).trim();
                    }
                }

                if (description) {
                    description = description.replace(/^[-–—]\s*/, '');
                }

                const titleHtml = title
                    ? `<div class="list-title">${formatListText(title).replace(/:?$/, '')}:</div>`
                    : '';
                const descriptionHtml = description
                    ? `<div class="list-description">${formatListText(description)}</div>`
                    : '';

                return `<div class="analysis-list-item"><span class="list-number">${number}</span><div class="list-body">${titleHtml || ''}${descriptionHtml || ''}</div></div>`;
            })

            // Bullet points with better spacing
            .replace(/^[-•]\s+(.+?)$/gm, '<div class="analysis-bullet" style="margin: 14px 0; padding-left: 16px; line-height: 1.8;"><span class="bullet" style="color: #3b82f6; margin-right: 8px; font-weight: bold;">•</span><span style="color: #374151;">$1</span></div>')

            // Paragraphs with proper spacing
            .replace(/\n\n/g, '</p><p class="analysis-p" style="margin: 18px 0; line-height: 1.8; color: #4b5563;">')
            .replace(/\n/g, '<br>');

        // Wrap in paragraph if not already wrapped
        if (!formatted.match(/^<[h3|h4|div|p]/)) {
            formatted = '<p class="analysis-p" style="margin: 18px 0; line-height: 1.8; color: #4b5563;">' + formatted + '</p>';
        }

        // Remove empty paragraphs
        formatted = formatted.replace(/<p class="analysis-p"[^>]*><\/p>/g, '');

        // Add section separator styling for Suggestions block
        formatted = formatted.replace(
            /Suggestions for Improvement:/gi,
            '<div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;"></div><strong style="font-size: 18px; color: #dc2626; display: block; margin-bottom: 16px;">💡 Suggestions for Improvement:</strong>'
        );

        console.log('[PL Coach] formatAnalysisContent output:', formatted.substring(0, 200));
        return formatted.trim();
    }
    // NEW METHOD: Generate actual content preview before showing modal
    requestImprovedContentFromAnalysis() {
        if (!this.currentAnalysis) {
            this.showNotification('No analysis available. Please analyze first.', 'warning');
            return;
        }

        const emptyFields = this.getEmptyTargetFields();
        const filledFields = this.getFilledFields();
        const targetFields = emptyFields.length > 0 ? emptyFields : filledFields;

        console.log('[PL Coach] requestImprovedContentFromAnalysis called');
        console.log('[PL Coach]   - Empty fields:', emptyFields);
        console.log('[PL Coach]   - Filled fields:', filledFields);
        console.log('[PL Coach]   - Target fields:', targetFields);

        if (targetFields.length === 0) {
            this.showNotification('No fields found to fill!', 'warning');
            return;
        }

        this.showNotification('Generating improved content for ' + targetFields.length + ' fields...', 'info', 10000);

        this.generateFieldContentFromAnalysis(targetFields, (generatedContent) => {
            console.log('[PL Coach] Content generation callback received');
            console.log('[PL Coach]   - Generated content:', generatedContent);

            const sanitizedContent = {};
            const missingFields = [];

            targetFields.forEach(fieldId => {
                const content = (generatedContent[fieldId] || '').trim();
                if (content && content.length > 50 && !/\[Content for/.test(content) && !/click AI Assist/i.test(content)) {
                    sanitizedContent[fieldId] = content;
                } else {
                    missingFields.push(fieldId);
                }
            });

            if (Object.keys(sanitizedContent).length === 0) {
                console.error('[PL Coach] No valid content generated!');
                this.showNotification('Failed to generate content. Please try again.', 'error');
                return;
            }

            if (missingFields.length) {
                console.warn('[PL Coach] Missing generated content for fields:', missingFields);
            }

            this.pendingGeneratedContent = sanitizedContent;
            console.log('[PL Coach] ✓ Stored pendingGeneratedContent:', this.pendingGeneratedContent);

            if (emptyFields.length === 0 && filledFields.length > 0) {
                const overrideFields = filledFields.filter(fieldId => sanitizedContent[fieldId]);
                if (!overrideFields.length) {
                    console.error('[PL Coach] No override fields have generated content.');
                    this.showNotification('No generated content available for the selected fields.', 'error');
                    return;
                }

                if (overrideFields.length < filledFields.length && missingFields.length) {
                    const missingLabels = missingFields
                        .map(fieldId => this.formContext.get(fieldId)?.label || fieldId)
                        .slice(0, 3)
                        .join(', ');
                    this.showNotification(`No new content generated for: ${missingLabels}${missingFields.length > 3 ? '...' : ''}`, 'warning');
                }

                console.log('[PL Coach] All fields filled - showing override confirmation');
                this.showOverrideConfirmation(overrideFields, sanitizedContent);
            } else {
                const fillTargets = emptyFields.filter(fieldId => sanitizedContent[fieldId]);
                if (!fillTargets.length) {
                    console.error('[PL Coach] No generated content available for empty fields.');
                    this.showNotification('No generated content available for empty fields. Please try again.', 'error');
                    return;
                }

                if (missingFields.length) {
                    const missingLabels = missingFields
                        .map(fieldId => this.formContext.get(fieldId)?.label || fieldId)
                        .slice(0, 3)
                        .join(', ');
                    this.showNotification(`No new content generated for: ${missingLabels}${missingFields.length > 3 ? '...' : ''}`, 'warning');
                }

                console.log('[PL Coach] Has empty fields - filling directly');
                this.fillFieldsWithGeneratedContent(fillTargets, sanitizedContent);
            }
        });
    }
    
    // NEW METHOD: Generate field-specific content from analysis
    generateFieldContentFromAnalysis(fieldIds, callback) {
        const context = this.gatherFormContext();

        console.log('[PL Coach] Generating content for fields:', fieldIds);

        // Build field descriptions with existing context
        const fieldDescriptions = fieldIds.map(fieldId => {
            const fieldInfo = this.formContext.get(fieldId);
            const label = fieldInfo ? fieldInfo.label : fieldId;
            const currentValue = fieldInfo ? fieldInfo.value : '';
            return `- **${label}**: ${currentValue ? 'Currently has: "' + currentValue.substring(0, 100) + '..."' : 'EMPTY - needs content'}`;
        }).join('\n');

        // Build existing context summary
        const existingContext = Object.entries(context)
            .filter(([key, val]) => val && val.length > 20)
            .map(([key, val]) => `**${key.replace(/_/g, ' ')}:** ${val.substring(0, 200)}`)
            .join('\n\n');

        const fieldLabels = fieldIds.map(fieldId => {
            const fieldInfo = this.formContext.get(fieldId);
            return fieldInfo ? fieldInfo.label : fieldId;
        });

        const promptTemplate = fieldLabels.map(label => `**${label}**\n[Write the complete, ready-to-paste content here. 3-5 sentences minimum. Be specific and detailed.]`).join('\n\n');

        const improveMessage = `You are helping fill empty form fields for a product launch workflow.

**EXISTING CONTEXT (use this for consistency):**
${existingContext || 'No existing context yet'}

**FIELDS TO FILL WITH ACTUAL CONTENT:**
${fieldDescriptions}

**CRITICAL INSTRUCTIONS:**
1. Generate ACTUAL ready-to-use content for each field listed
2. Each field needs 3-5 detailed sentences (100+ words total per field)
3. Use existing context to maintain consistency and specificity
4. Be concrete and actionable, not generic summaries
5. Format response EXACTLY like this:

${promptTemplate}

CRITICAL: Write ACTUAL field content, NOT suggestions or descriptions.
Generate the content now:`;

        jQuery.ajax({
            url: productLaunch.ajax_url,
            type: 'POST',
            data: {
                action: 'product_launch_chat',
                phase: this.getCurrentPhase(),
                message: improveMessage,
                history: JSON.stringify([]),
                context: JSON.stringify(context),
                nonce: productLaunch.nonce
            },
            timeout: 60000,
            success: (response) => {
                if (response.success) {
                    console.log('[PL Coach] AI Response received:', response.data);

                    // Parse the structured response
                    const parsedContent = this.parseFieldContentResponse(response.data, fieldIds);
                    console.log('[PL Coach] Parsed content:', parsedContent);

                    callback(parsedContent);
                } else {
                    this.showNotification('Failed to generate content', 'error');
                    console.error('[PL Coach] Generation failed:', response);
                }
            },
            error: (xhr, status, error) => {
                this.showNotification('Error generating content', 'error');
                console.error('[PL Coach] Ajax error:', status, error);
            }
        });
    }
    
    // NEW METHOD: Parse AI response into field => content map
    parseFieldContentResponse(aiResponse, fieldIds) {
        const parsed = {};
        const seenContent = new Set();
        const usedParagraphIndices = new Set(); // Track paragraph positions already consumed
        const normalizeSignature = (text) => (text || '').replace(/\s+/g, ' ').trim().toLowerCase();

        console.log('[PL Coach] Parsing response for fields:', fieldIds);

        const responseText = this.coerceToText(aiResponse).replace(/\r\n/g, '\n');

        let structuredResponse = null;
        if (aiResponse && typeof aiResponse === 'object' && !Array.isArray(aiResponse)) {
            structuredResponse = aiResponse;
        } else {
            try {
                const maybeJson = JSON.parse(responseText);
                if (maybeJson && typeof maybeJson === 'object') {
                    structuredResponse = maybeJson;
                }
            } catch (e) {
                // Not JSON - ignore
            }
        }

        const structuredMap = structuredResponse ? this.flattenStructuredResponse(structuredResponse) : null;

        fieldIds.forEach(fieldId => {
            const fieldInfo = this.formContext.get(fieldId);
            if (!fieldInfo) {
                return;
            }

            const primaryLabel = fieldInfo?.label || this.humanizeFieldName(fieldId) || fieldId;
            const labelCandidates = new Set([
                primaryLabel,
                fieldId,
                fieldId.replace(/_/g, ' '),
                this.humanizeFieldName(fieldId)
            ]);

            if (fieldInfo?.label) {
                labelCandidates.add(fieldInfo.label.replace(/_/g, ' '));
                labelCandidates.add(this.humanizeFieldName(fieldInfo.label));
            }

            const candidateLabels = Array.from(labelCandidates)
                .map(label => (label || '').toString().trim())
                .filter(Boolean);

            const normalizedCandidates = candidateLabels.map(label => this.normalizeFieldKey(label));

            console.log('[PL Coach] Looking for content for:', primaryLabel, '| candidates:', candidateLabels);

            // Attempt structured lookup first when available
            if (structuredMap && !parsed[fieldId]) {
                for (const candidateKey of normalizedCandidates) {
                    if (structuredMap.has(candidateKey)) {
                        const structuredValue = this.coerceToText(structuredMap.get(candidateKey)).trim();
                        if (structuredValue.length > 20) {
                            const signature = normalizeSignature(structuredValue);
                            if (!signature || seenContent.has(signature)) {
                                continue;
                            }

                            parsed[fieldId] = structuredValue;
                            seenContent.add(signature);
                            console.log(`[PL Coach] ✓ Found structured content for ${primaryLabel}:`, structuredValue.substring(0, 100) + '...');
                            break;
                        }
                    }
                }
            }

            if (parsed[fieldId]) {
                return;
            }

            // Try multiple patterns with field name variations
            const boundary = '(?=\\n{2,}|\\n\\*\\*|\\n\\d+\\.\\s|$)';
            let foundContent = false;

            for (const candidate of candidateLabels) {
                if (foundContent) break;

                const escapedLabel = this.escapeRegex(candidate);
                const patterns = [
                    new RegExp(`\\*\\*${escapedLabel}\\*\\*[:\\s]*\\n+([\\s\\S]{40,2000}?)${boundary}`, 'i'),
                    new RegExp(`\\*\\*${escapedLabel}\\*\\*[:\\s-]+([\\s\\S]{40,2000}?)${boundary}`, 'i'),
                    new RegExp(`^${escapedLabel}[:\\s-]+([\\s\\S]{40,2000}?)${boundary}`, 'im'),
                    new RegExp(`^${escapedLabel}\\s*\\n+([\\s\\S]{40,2000}?)${boundary}`, 'im'),
                    new RegExp(`\\d+\\.\\s*${escapedLabel}[:\\s-]+([\\s\\S]{40,2000}?)${boundary}`, 'i')
                ];

                for (let i = 0; i < patterns.length; i++) {
                    try {
                        const match = responseText.match(patterns[i]);
                        if (match && match[1]) {
                            let matchedContent = match[1]
                                .replace(new RegExp(`^${escapedLabel}[:\\s-]*`, 'i'), '')
                                .replace(/^\d+\.\s*/, '')
                                .replace(/^[-•]\s*/, '')
                                .replace(/^"|"$/g, '')
                                .trim();

                            if (matchedContent.length > 40) {
                                const signature = normalizeSignature(matchedContent);
                                if (signature && !seenContent.has(signature)) {
                                    parsed[fieldId] = matchedContent;
                                    seenContent.add(signature);
                                    console.log(`[PL Coach] ✓ Found content for ${primaryLabel}:`, matchedContent.substring(0, 100) + '...');
                                    foundContent = true;
                                    break;
                                }
                            }
                        }
                    } catch (e) {
                        console.warn(`[PL Coach] Pattern ${i + 1} failed for ${primaryLabel} (${candidate}):`, e);
                    }
                }
            }

            if (parsed[fieldId]) {
                return;
            }

            // If still no match, try paragraph extraction
            console.warn(`[PL Coach] No direct match for ${primaryLabel}, trying paragraph extraction`);

            const paragraphs = responseText
                .split(/\n{2,}/)
                .map(p => p.trim())
                .filter(p => p.length > 40 && !p.startsWith('#'))
                .map((text, index) => ({ text, index, signature: normalizeSignature(text) }))
                .filter(item => item.text.length > 0);

            let matchedParagraphEntry = null;
            for (const entry of paragraphs) {
                if (usedParagraphIndices.has(entry.index)) {
                    continue;
                }
                if (seenContent.has(entry.signature)) {
                    continue;
                }

                if (candidateLabels.some(label => new RegExp(`\\b${this.escapeRegex(label)}\\b`, 'i').test(entry.text))) {
                    matchedParagraphEntry = entry;
                    break;
                }
            }

            if (!matchedParagraphEntry && paragraphs.length) {
                const availableParagraphs = paragraphs.filter(entry => !usedParagraphIndices.has(entry.index) && !seenContent.has(entry.signature));
                const fieldIndex = fieldIds.indexOf(fieldId);
                if (availableParagraphs.length) {
                    matchedParagraphEntry = availableParagraphs[fieldIndex] || availableParagraphs[availableParagraphs.length - 1];
                }
            }

            if (matchedParagraphEntry) {
                let cleanedParagraph = matchedParagraphEntry.text;
                candidateLabels.forEach(label => {
                    cleanedParagraph = cleanedParagraph.replace(new RegExp(`^${this.escapeRegex(label)}[:\\s-]*`, 'i'), '');
                });
                cleanedParagraph = cleanedParagraph
                    .replace(/^\d+\.\s*/, '')
                    .replace(/^[-•]\s*/, '')
                    .trim();

                if (cleanedParagraph.length > 40) {
                    const signature = normalizeSignature(cleanedParagraph);
                    usedParagraphIndices.add(matchedParagraphEntry.index);
                    if (!seenContent.has(signature)) {
                        parsed[fieldId] = cleanedParagraph;
                        seenContent.add(signature);
                        console.log(`[PL Coach] ✓ Extracted paragraph for ${primaryLabel}:`, cleanedParagraph.substring(0, 100) + '...');
                    }
                }
            }

            // Final fallback - generate placeholder
            if (!parsed[fieldId]) {
                parsed[fieldId] = `[Content for ${primaryLabel} - click AI Assist button to generate]`;
                console.warn(`[PL Coach] ✗ Could not extract content for ${primaryLabel}`);
            }
        });

        return parsed;
    }

    coerceToText(value) {
        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'string') {
            return value;
        }

        if (Array.isArray(value)) {
            return value.map(item => this.coerceToText(item)).filter(Boolean).join('\n');
        }

        if (typeof value === 'object') {
            if (typeof value.text === 'string') {
                return value.text;
            }

            if (typeof value.content === 'string') {
                return value.content;
            }

            if (Array.isArray(value.content)) {
                return value.content.map(part => this.coerceToText(part)).filter(Boolean).join('\n');
            }

            if (typeof value.message === 'string') {
                return value.message;
            }

            if (typeof value.response === 'string') {
                return value.response;
            }

            if (Array.isArray(value.choices)) {
                return value.choices
                    .map(choice => this.coerceToText(choice.message?.content || choice.text || choice))
                    .filter(Boolean)
                    .join('\n');
            }

            try {
                return JSON.stringify(value);
            } catch (e) {
                return String(value);
            }
        }

        return String(value);
    }

    flattenStructuredResponse(structured) {
        const map = new Map();
        const stack = [structured];

        while (stack.length) {
            const current = stack.pop();
            if (!current) continue;

            if (Array.isArray(current)) {
                current.forEach(item => stack.push(item));
                continue;
            }

            if (typeof current !== 'object') {
                continue;
            }

            Object.entries(current).forEach(([key, value]) => {
                if (!key) return;
                const normalizedKey = this.normalizeFieldKey(key);

                if (typeof value === 'string') {
                    if (!map.has(normalizedKey)) {
                        map.set(normalizedKey, value.trim());
                    }
                } else if (value && typeof value === 'object') {
                    stack.push(value);
                }
            });
        }

        return map;
    }

    normalizeFieldKey(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/_{2,}/g, '_')
            .replace(/^_|_$/g, '');
    }

    // Helper method for regex escaping
    escapeRegex(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // MODIFIED: showOverrideConfirmation now accepts generated content
    showOverrideConfirmation(filledFields, generatedContent = null) {
        // Store the generated content so the modal can access it
        this.pendingGeneratedContent = generatedContent;
        
        const fieldList = filledFields.map(fieldId => {
            const fieldInfo = this.formContext.get(fieldId);
            return fieldInfo ? fieldInfo.label : fieldId;
        }).slice(0, 5).join(', ');
        
        const moreCount = filledFields.length > 5 ? ` and ${filledFields.length - 5} more` : '';
        
        const confirmHtml = `
            <div class="override-confirmation-modal">
                <div class="override-modal-content">
                    <h3>Override Existing Content?</h3>
                    <p>All ${filledFields.length} fields already have content. Would you like to:</p>
                    <div class="override-options">
                        <button class="button button-large override-btn" data-action="override">
                            <span class="dashicons dashicons-update"></span>
                            Replace All Fields (${filledFields.length})
                        </button>
                        <button class="button button-large button-secondary cancel-override-btn">
                            <span class="dashicons dashicons-no-alt"></span>
                            Cancel
                        </button>
                    </div>
                    <p class="override-warning">
                        <span class="dashicons dashicons-warning"></span>
                        This will replace: ${fieldList}${moreCount}
                    </p>
                </div>
            </div>
        `;
        
        jQuery('body').append(confirmHtml);
        
        jQuery('.override-btn').on('click', () => {
            jQuery('.override-confirmation-modal').remove();
            this.fillFieldsWithGeneratedContent(filledFields, generatedContent);
        });
        
        jQuery('.cancel-override-btn').on('click', () => {
            jQuery('.override-confirmation-modal').remove();
        });
    }
    
    // NEW METHOD: Fill fields with pre-generated content
    fillFieldsWithGeneratedContent(fieldIds, generatedContent) {
        if (!generatedContent) {
            this.showNotification('No content available to fill fields', 'error');
            return;
        }
        
        let filledCount = 0;
        
        fieldIds.forEach(fieldId => {
            const content = generatedContent[fieldId];
            if (content) {
                this.fillField(fieldId, content);
                filledCount++;
            }
        });
        
        if (filledCount > 0) {
            this.showNotification(`✅ ${filledCount} fields filled with improved content!`, 'success');
            this.immediateFormSave();
        }
        
        // Clear pending content
        this.pendingGeneratedContent = null;
    }
    
    getFilledFields() {
        const filled = [];
        this.formContext.forEach((fieldInfo, fieldId) => {
            const value = fieldInfo.element.val();
            if (value && value.trim() !== '') {
                filled.push(fieldId);
            }
        });
        return filled;
    }
    
    fillFieldsSequentially(fieldIds, index) {
        if (index >= fieldIds.length) {
            this.showNotification('✅ All fields have been filled with improved content!', 'success');
            this.immediateFormSave();
            return;
        }
        
        const fieldId = fieldIds[index];
        const fieldInfo = this.formContext.get(fieldId);
        
        if (!fieldInfo) {
            this.fillFieldsSequentially(fieldIds, index + 1);
            return;
        }
        
        this.showNotification(`Filling field ${index + 1} of ${fieldIds.length}: ${fieldInfo.label}...`, 'info', 2000);
        
        const context = this.gatherFormContext();
        
        this.showFieldLoading(fieldId);
        
        jQuery.ajax({
            url: productLaunch.ajax_url,
            type: 'POST',
            data: {
                action: 'product_launch_field_assist',
                phase: this.getCurrentPhase(),
                field_id: fieldId,
                context: JSON.stringify(context),
                nonce: productLaunch.nonce
            },
            timeout: 30000,
            success: (response) => {
                this.hideFieldLoading(fieldId);
                
                if (response.success) {
                    this.fillField(fieldId, response.data);
                }
                
                setTimeout(() => {
                    this.fillFieldsSequentially(fieldIds, index + 1);
                }, 800);
            },
            error: () => {
                this.hideFieldLoading(fieldId);
                
                setTimeout(() => {
                    this.fillFieldsSequentially(fieldIds, index + 1);
                }, 800);
            }
        });
    }
    
    getEmptyTargetFields() {
        const empty = [];
        this.formContext.forEach((fieldInfo, fieldId) => {
            const value = fieldInfo.element.val();
            if (!value || value.trim() === '') {
                empty.push(fieldId);
            }
        });
        return empty;
    }
    
    applyMessageToForm(messageText) {
        const emptyFields = this.getEmptyTargetFields();
        
        if (emptyFields.length === 0) {
            this.showNotification('All fields are already filled. Clear some fields first or use "Analyze & Suggest Improvements" to override.', 'info');
            return;
        }
        
        this.showNotification(`Filling ${emptyFields.length} empty fields...`, 'info', 3000);
        this.fillFieldsSequentially(emptyFields, 0);
    }
    
    humanizeFieldName(fieldName) {
        return fieldName
            .replace(/_/g, ' ')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
    
    showModal() {
        jQuery('#product-launch-modal').fadeIn(300);
        jQuery('body').addClass('modal-open');
    }
    
    closeModal() {
        jQuery('#product-launch-modal').fadeOut(300);
        jQuery('body').removeClass('modal-open');
        jQuery('#product-launch-modal').css('z-index', '');
    }
    
    showLoading() {
        this.isLoading = true;
        const loadingHtml = `
            <div class="message ai-message loading-message">
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        `;
        jQuery('.chat-messages').append(loadingHtml);
        jQuery('.chat-messages').scrollTop(jQuery('.chat-messages')[0].scrollHeight);
    }
    
    hideLoading() {
        this.isLoading = false;
        jQuery('.loading-message').remove();
    }
    
    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
    
    showNotification(message, type = 'info', duration = 5000) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        const notification = jQuery('<div class="pl-notification"></div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '16px 24px',
                background: colors[type] || colors.info,
                color: 'white',
                borderRadius: '8px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                zIndex: 100000,
                maxWidth: '400px',
                animation: 'slideInRight 0.3s ease'
            })
            .text(message);
        
        jQuery('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(300, function() {
                jQuery(this).remove();
            });
        }, duration);
    }
    
    autoResize(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
    }
    
    autoSaveProgress() {
        clearTimeout(this.autoSaveTimeout);
        this.autoSaveTimeout = setTimeout(() => {
            this.immediateFormSave();
        }, 2000);
    }
    
    updateProgressTracker() {
        // Placeholder
    }
    
    loadPreviousContext() {
        const phase = this.getCurrentPhase();
        if (!phase || phase === 'general') return;
        
        const storageKey = `pl_context_${phase}`;
        const savedContext = localStorage.getItem(storageKey);
        
        if (savedContext) {
            try {
                const context = JSON.parse(savedContext);
                Object.entries(context).forEach(([fieldId, value]) => {
                    const fieldInfo = this.formContext.get(fieldId);
                    if (fieldInfo && !fieldInfo.element.val()) {
                        fieldInfo.element.val(value);
                    }
                });
            } catch (e) {
                console.warn('Failed to load previous context:', e);
            }
        }
    }
    
    testAPIConnection() {
        this.showNotification('Testing API connection...', 'info');
    }
}

// Initialize
jQuery(document).ready(function() {
    if (typeof productLaunch !== 'undefined') {
        window.productLaunchCoach = new EnhancedProductLaunchCoach();
    }
});

} // End file guard