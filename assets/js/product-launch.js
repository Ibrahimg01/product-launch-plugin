/* PL per-file guard */
if (typeof window.__PL_FILE_GUARDS === 'undefined') { window.__PL_FILE_GUARDS = {}; }
if (window.__PL_FILE_GUARDS['assets/js/product-launch.js']) { console.warn('Duplicate JS skipped:', 'assets/js/product-launch.js'); }
else { window.__PL_FILE_GUARDS['assets/js/product-launch.js'] = 1;

/*
 * Product Launch Plugin - Complete Fixed JavaScript
 * Version 2.3.56 - Field-Specific AI Suggestions Fix
 * 
 * FIXES APPLIED:
 * - Bug #1: Modal z-index conflict (modal behind chat)
 * - Bug #2: Override existing fields option
 * - Bug #3: Restored "Apply to Form" button
 * - Bug #4: AI Assist button styling
 * - Bug #5: Generate actual content preview before showing selective modal
 * - Bug #6: Resolved all parsing conflicts with duplicate detection
 * - Bug #7: Fixed AI suggestions applying same text to all fields
 * - Bug #8: Enhanced field parsing with multiple pattern matching
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
        this.pendingGeneratedContent = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeComponents();
        this.scanFormFields();
        this.loadPreviousContext();
    }

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
        jQuery(document).on('click', '.start-ai-coaching', (e) => {
            e.preventDefault();
            this.startCoachingSession(jQuery(e.currentTarget).data('phase'));
        });
        
        jQuery(document).on('click', '.modal-close', (e) => {
            e.preventDefault();
            this.closeModal();
        });
        
        jQuery(document).on('submit', '.chat-form', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        jQuery(document).on('keypress', '.chat-textarea', (e) => {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        jQuery(document).on('click', '.ai-assist-btn', (e) => {
            e.preventDefault();
            const fieldId = jQuery(e.currentTarget).data('field');
            this.requestFieldAssistance(fieldId);
        });
        
        jQuery(document).on('input change', '.ai-fillable', (e) => {
            this.updateFormContext();
            this.autoSaveProgress();
        });
        
        jQuery(document).on('click', '.quick-action-btn', (e) => {
            e.preventDefault();
            const action = jQuery(e.currentTarget).data('action');
            this.handleQuickAction(action);
        });
        
        jQuery(document).on('click', '.apply-to-form-btn', (e) => {
            e.preventDefault();
            const $message = jQuery(e.currentTarget).closest('.message');
            const messageText = $message.find('.message-text').text();
            this.applyMessageToForm(messageText);
        });
        
        jQuery(document).on('click', '.test-api-connection', (e) => {
            e.preventDefault();
            this.testAPIConnection();
        });
        
        jQuery(document).on('click', '#product-launch-modal', (e) => {
            if (e.target === e.currentTarget) {
                this.closeModal();
            }
        });
        
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
        message = this.formatAIResponse(message);

        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^(.*)$/gm, function(match) {
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

        jQuery('.improvement-modal-overlay').remove();
        jQuery('#product-launch-modal').css('z-index', '99999');

        const formattedContent = this.formatAnalysisContent(analysisContent);
        console.log('[PL Coach] Modal will show formatted content');

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

        jQuery('body').append(modalHtml);
        console.log('[PL Coach] Modal HTML appended');

        this.currentAnalysis = analysisContent;

        jQuery('.improvement-close, .improvement-close-btn').on('click', () => {
            console.log('[PL Coach] Closing modal');
            jQuery('.improvement-modal-overlay').fadeOut(300, function() {
                jQuery(this).remove();
                jQuery('#product-launch-modal').css('z-index', '');
            });
        });

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

        content = this.formatAIResponse(content);
        content = content.replace(/(###?\s+[^\n]+?)(\d+\.)/g, '$1\n$2');
        content = content.replace(/(^|\n)(\d+)\.\s+/g, (match, prefix, number) => `${prefix}\n${number}. `);

        const formatListText = (value) => value
            .replace(/\n{2,}/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .trim();

        let formatted = content
            .replace(/###\s+(.+?)(\n|$)/g, '<h3 class="analysis-h3" style="margin-top: 24px; margin-bottom: 12px; font-size: 18px; color: #1f2937; font-weight: 700;">$1</h3>\n')
            .replace(/##\s+(.+?)(\n|$)/g, '<h4 class="analysis-h4" style="margin-top: 20px; margin-bottom: 10px; font-size: 16px; color: #374151; font-weight: 600;">$1</h4>\n')
            .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #1f2937; font-weight: 600;">$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
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
            .replace(/^[-•]\s+(.+?)$/gm, '<div class="analysis-bullet" style="margin: 14px 0; padding-left: 16px; line-height: 1.8;"><span class="bullet" style="color: #3b82f6; margin-right: 8px; font-weight: bold;">•</span><span style="color: #374151;">$1</span></div>')
            .replace(/\n\n/g, '</p><p class="analysis-p" style="margin: 18px 0; line-height: 1.8; color: #4b5563;">')
            .replace(/\n/g, '<br>');

        if (!formatted.match(/^<[h3|h4|div|p]/)) {
            formatted = '<p class="analysis-p" style="margin: 18px 0; line-height: 1.8; color: #4b5563;">' + formatted + '</p>';
        }

        formatted = formatted.replace(/<p class="analysis-p"[^>]*><\/p>/g, '');
        formatted = formatted.replace(
            /Suggestions for Improvement:/gi,
            '<div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;"></div><strong style="font-size: 18px; color: #dc2626; display: block; margin-bottom: 16px;">💡 Suggestions for Improvement:</strong>'
        );

        console.log('[PL Coach] formatAnalysisContent output:', formatted.substring(0, 200));
        return formatted.trim();
    }
    
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
    
    generateFieldContentFromAnalysis(fieldIds, callback) {
        const context = this.gatherFormContext();

        console.log('[PL Coach] Generating content for fields:', fieldIds);

        const fieldDescriptions = fieldIds.map(fieldId => {
            const fieldInfo = this.formContext.get(fieldId);
            const label = fieldInfo ? fieldInfo.label : fieldId;
            const currentValue = fieldInfo ? fieldInfo.value : '';
            return `- **${label}**: ${currentValue ? 'Currently has: "' + currentValue.substring(0, 100) + '..."' : 'EMPTY - needs content'}`;
        }).join('\n');

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
    
    parseFieldContentResponse(aiResponse, fieldIds) {
        const parsed = {};
        const seenContent = new Set();
        const usedParagraphIndices = new Set();
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
                // Not JSON
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

    escapeRegex(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\                const leadingSplit = trimmedText.match(/^([^:\-\n]{1,120}?)(?:\s*[:\-–—]\s+)([\s\S]*)');
    }
    
    showOverrideConfirmation(filledFields, generatedContent = null) {
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

/**
 * ENHANCED "Fill Missing Fields" handler - CONSOLIDATED VERSION
 * Fixes all parsing conflicts and field detection issues
 */
jQuery(document).on('click', '.fill-missing-fields, button:contains("Fill Missing Fields")', function(e) {
    e.preventDefault();
    console.log('[PL Coach] Fill Missing Fields clicked');

    const $button = jQuery(this);
    const $modal = $button.closest('.ai-modal, .coach-modal');

    if ($modal.length === 0) {
        console.error('[PL Coach] Modal not found');
        return;
    }

    const $chatMessages = $modal.find('.chat-messages');
    const $lastAssistantMsg = $chatMessages.find('.message.assistant').last();

    if ($lastAssistantMsg.length === 0) {
        console.error('[PL Coach] No assistant message found');
        alert('No AI response found. Please chat with the AI coach first.');
        return;
    }

    const fullResponse = $lastAssistantMsg.find('.message-content').text();
    console.log('[PL Coach] Full AI response length:', fullResponse.length);

    const phase = $modal.data('phase') || $button.data('phase') || 'unknown';
    console.log('[PL Coach] Current phase:', phase);

    const fieldSuggestions = parseAIResponseForFields(fullResponse, phase);
    console.log('[PL Coach] Parsed field suggestions:', fieldSuggestions);

    if (Object.keys(fieldSuggestions).length === 0) {
        console.warn('[PL Coach] No field suggestions found in response');
        alert('No field suggestions found. Try asking the AI coach to help fill specific fields.');
        return;
    }

    const currentValues = getCurrentFormValues(phase);
    console.log('[PL Coach] Current form values:', currentValues);

    showFieldReplacementModal(fieldSuggestions, currentValues, phase);
});

/**
 * Parse AI response to extract field-specific content
 * ENHANCED with comprehensive pattern matching and fallback strategies
 */
function parseAIResponseForFields(responseText, phase) {
    const fields = {};

    if (!responseText) {
        return fields;
    }

    const normalizedText = responseText
        .replace(/\r\n/g, '\n')
        .replace(/\u00A0/g, ' ')
        .replace(/\]\s*(?=\[)/g, ']\n')
        .trim();

    // Field patterns by phase with multiple matching strategies
    const fieldPatterns = {
        'create_offer': {
            'main_offer': [
                /(?:main offer|core offering|product offering)[:\s]+([^]*?)(?=\n\n|pricing|bonus|guarantee|$)/i,
                /\[(?:main[_\s]?offer|core[_\s]?offering|product[_\s]?offering)\]\s*([^]*?)(?=\[|$)/i,
                /(?:^|\n)main offer[:\s]*([^]*?)(?=\n\n|$)/im
            ],
            'pricing_strategy': [
                /(?:pricing strategy|pricing|price point|investment)[:\s]+([^]*?)(?=\n\n|bonus|guarantee|$)/i,
                /\[(?:pricing[_\s]?strategy|pricing|price[_\s]?point|investment)\]\s*([^]*?)(?=\[|$)/i
            ],
            'bonuses': [
                /(?:bonuses?|bonus items?)[:\s]+([^]*?)(?=\n\n|guarantee|pricing|$)/i,
                /\[(?:bonuses?|bonus[_\s]?items?)\]\s*([^]*?)(?=\[|$)/i
            ],
            'guarantee': [
                /(?:guarantee|money.back|refund policy)[:\s]+([^]*?)(?=\n\n|$)/i,
                /\[(?:guarantee|money[_\s]?back|refund[_\s]?policy)\]\s*([^]*?)(?=\[|$)/i
            ]
        },
        'market_clarity': {
            'target_audience': [
                /(?:target audience|ideal customer|ideal client)[:\s]+([^]*?)(?=\n\n|pain points|value|$)/i,
                /\[(?:target[_\s]?audience|ideal[_\s]?customer|ideal[_\s]?client)\]\s*([^]*?)(?=\[|$)/i
            ],
            'pain_points': [
                /(?:pain points?|problems?|challenges?)[:\s]+([^]*?)(?=\n\n|value|market|$)/i,
                /\[(?:pain[_\s]?points?|problems?|challenges?)\]\s*([^]*?)(?=\[|$)/i
            ],
            'value_proposition': [
                /(?:value proposition|unique value|unique selling proposition|usp)[:\s]+([^]*?)(?=\n\n|market|competitor|$)/i,
                /\[(?:value[_\s]?proposition|unique[_\s]?value|unique[_\s]?selling[_\s]?proposition|usp)\]\s*([^]*?)(?=\[|$)/i
            ]
        },
        'email_sequences': {
            'email_subject': [
                /(?:subject lines?|email subject|headline)[:\s]+([^]*?)(?=\n\n|email body|preview|$)/i,
                /\[(?:email[_\s]?subject|subject[_\s]?lines?)\]\s*([^]*?)(?=\[|$)/i
            ],
            'email_body': [
                /(?:email body|email copy|email content|message)[:\s]+([^]*?)(?=\n\n|subject|preview|$)/i,
                /\[(?:email[_\s]?body|email[_\s]?copy|email[_\s]?content|message)\]\s*([^]*?)(?=\[|$)/i
            ]
        }
    };

    // Alias mapping for field name variations
    const aliasMap = {
        'main_offer': 'main_offer',
        'core_offering': 'main_offer',
        'product_offering': 'main_offer',
        'offer': 'main_offer',
        'pricing_strategy': 'pricing_strategy',
        'pricing': 'pricing_strategy',
        'price_point': 'pricing_strategy',
        'investment': 'pricing_strategy',
        'bonuses': 'bonuses',
        'bonus_items': 'bonuses',
        'bonus': 'bonuses',
        'guarantee': 'guarantee',
        'money_back': 'guarantee',
        'refund_policy': 'guarantee',
        'target_audience': 'target_audience',
        'ideal_customer': 'target_audience',
        'ideal_client': 'target_audience',
        'pain_points': 'pain_points',
        'pain_point': 'pain_points',
        'customer_challenges': 'pain_points',
        'challenges': 'pain_points',
        'problems': 'pain_points',
        'value_proposition': 'value_proposition',
        'unique_value': 'value_proposition',
        'unique_selling_proposition': 'value_proposition',
        'usp': 'value_proposition',
        'email_subject': 'email_subject',
        'subject_line': 'email_subject',
        'subject_lines': 'email_subject',
        'headline': 'email_subject',
        'email_body': 'email_body',
        'email_copy': 'email_body',
        'email_content': 'email_body',
        'message_body': 'email_body',
        'ad_headline': 'ad_headline',
        'ad_body': 'ad_body',
        'ad_copy': 'ad_body'
    };

    const phasePatterns = fieldPatterns[phase] || {};
    const relevantFieldIds = new Set(Object.keys(phasePatterns));

    const normalizeLabel = (label) => label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');

    const cleanContent = (content) => content
        .replace(/\[\/[^\]]+\]/g, '')
        .replace(/^[\s:\-–]+/, '')
        .trim();

    const resolveFieldId = (label) => {
        const normalizedLabel = normalizeLabel(label);

        if (aliasMap[normalizedLabel]) {
            return aliasMap[normalizedLabel];
        }

        let singularLabel = null;
        if (normalizedLabel.endsWith('es')) {
            singularLabel = normalizedLabel.replace(/es$/, '');
        } else if (normalizedLabel.endsWith('s') && !normalizedLabel.endsWith('us')) {
            singularLabel = normalizedLabel.replace(/s$/, '');
        }

        if (singularLabel && aliasMap[singularLabel]) {
            return aliasMap[singularLabel];
        }

        return null;
    };

    const assignField = (fieldId, rawContent, source) => {
        if (!fieldId) {
            return;
        }

        if (relevantFieldIds.size && !relevantFieldIds.has(fieldId)) {
            return;
        }

        const content = cleanContent(rawContent);

        if (content.length < 20) {
            return;
        }

        if (!fields[fieldId] || content.length > fields[fieldId].length) {
            fields[fieldId] = content;
            console.log(`[PL Coach] ${source} matched: ${fieldId}, length: ${content.length}`);
        }
    };

    // Pass 1: Parse structured [FIELD] blocks
    const bracketRegex = /\[([^\]]+)\]\s*:?[\t ]*([\s\S]*?)(?=\n\s*\[[^\]]+\]\s*:|$)/gi;
    let bracketMatch;
    while ((bracketMatch = bracketRegex.exec(normalizedText)) !== null) {
        const label = bracketMatch[1];
        const fieldId = resolveFieldId(label);
        if (fieldId) {
            assignField(fieldId, bracketMatch[2], 'Structured');
        } else {
            console.log('[PL Coach] Structured label not mapped:', label);
        }
    }

    // Pass 2: Parse "Field Name:" style sections line-by-line
    const lines = normalizedText.split(/\n+/);
    let currentLabel = null;
    let buffer = [];

    const flushBuffer = () => {
        if (!currentLabel) {
            return;
        }
        const fieldId = resolveFieldId(currentLabel);
        if (fieldId && buffer.length) {
            assignField(fieldId, buffer.join('\n'), 'Section');
        } else if (!fieldId) {
            console.log('[PL Coach] Section label not mapped:', currentLabel);
        }
        currentLabel = null;
        buffer = [];
    };

    lines.forEach((line) => {
        const labelMatch = line.match(/^\s*(?:\[)?([A-Za-z][A-Za-z0-9 _\-/]{2,})\]?\s*:\s*(.*)$/);
        if (labelMatch) {
            flushBuffer();
            currentLabel = labelMatch[1];
            const remainder = labelMatch[2];
            if (remainder) {
                buffer.push(remainder.trim());
            }
        } else if (currentLabel) {
            buffer.push(line.trim());
        }
    });
    flushBuffer();

    // Pass 3: Use regex patterns for any remaining fields
    for (const [fieldId, patterns] of Object.entries(phasePatterns)) {
        if (fields[fieldId]) {
            continue;
        }

        for (const pattern of patterns) {
            const match = normalizedText.match(pattern);
            if (match && match[1]) {
                assignField(fieldId, match[1], 'Regex');
                if (fields[fieldId]) {
                    break;
                }
            }
        }
    }

    // Pass 4: Fallback paragraph/keyword detection
    const missingFields = Object.keys(phasePatterns).filter((fieldId) => !fields[fieldId]);

    if (missingFields.length) {
        console.log('[PL Coach] Using fallback paragraph matching for', missingFields);
        const paragraphs = normalizedText.split(/\n{2,}|\r{2,}/);

        const keywordMap = {
            'main_offer': ['offer', 'program', 'course', 'service', 'product'],
            'pricing_strategy': ['price', 'pricing', 'cost', ', 'investment', 'payment'],
            'bonuses': ['bonus', 'bonuses', 'extra', 'include'],
            'guarantee': ['guarantee', 'refund', 'money back', 'risk-free'],
            'target_audience': ['audience', 'customer', 'client', 'who', 'buyer'],
            'pain_points': ['pain', 'problem', 'struggle', 'challenge', 'frustration'],
            'value_proposition': ['value proposition', 'unique value', 'usp', 'differentiates'],
            'email_subject': ['subject', 'headline'],
            'email_body': ['email', 'message', 'copy']
        };

        missingFields.forEach((fieldId) => {
            const keywords = keywordMap[fieldId] || [];
            for (const paragraph of paragraphs) {
                const lowerPara = paragraph.toLowerCase();
                if (keywords.some((kw) => lowerPara.includes(kw)) && paragraph.trim().length > 40) {
                    assignField(fieldId, paragraph, 'Fallback');
                    if (fields[fieldId]) {
                        break;
                    }
                }
            }
        });
    }

    return fields;
}

/**
 * Get current form field values
 */
function getCurrentFormValues(phase) {
    const values = {};

    const $form = jQuery('form, .phase-form, [data-phase="' + phase + '"]').first();

    if ($form.length === 0) {
        console.warn('[PL Coach] Form not found, searching globally');
        jQuery('textarea, input[type="text"]').each(function() {
            const $field = jQuery(this);
            const fieldId = $field.attr('id') || $field.attr('name');
            if (fieldId) {
                values[fieldId] = $field.val() || '';
            }
        });
    } else {
        $form.find('textarea, input[type="text"]').each(function() {
            const $field = jQuery(this);
            const fieldId = $field.attr('id') || $field.attr('name');
            if (fieldId) {
                values[fieldId] = $field.val() || '';
            }
        });
    }

    console.log('[PL Coach] Found form fields:', Object.keys(values));
    return values;
}

/**
 * Show field replacement modal with suggestions
 */
function showFieldReplacementModal(suggestions, currentValues, phase) {
    jQuery('.pl-field-replacement-modal').remove();

    let modalHtml = `
        <div class="pl-field-replacement-modal" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <div class="modal-content" style="
                background: white;
                padding: 30px;
                border-radius: 8px;
                max-width: 800px;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">Choose Fields to Replace</h2>
                    <button class="close-modal" style="
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                    ">&times;</button>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <button class="select-all-modal-fields" style="margin-right: 10px;">Select All</button>
                    <button class="deselect-all-modal-fields">Deselect All</button>
                    <span style="float: right; color: #666;" class="selection-count">0 of ${Object.keys(suggestions).length} selected</span>
                </div>
                
                <div class="field-list">
    `;

    let fieldCount = 0;

    for (const [fieldId, suggestedContent] of Object.entries(suggestions)) {
        const currentContent = currentValues[fieldId] || '';
        const fieldLabel = getFieldLabel(fieldId);

        fieldCount++;

        modalHtml += `
            <div class="field-comparison" style="
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
            ">
                <label style="display: flex; align-items: start; cursor: pointer;">
                    <input type="checkbox" 
                           class="field-checkbox" 
                           value="${fieldId}" 
                           data-suggested="${escapeHtml(suggestedContent)}"
                           style="margin-right: 10px; margin-top: 4px;"
                           checked>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 10px; font-size: 14px; color: #333;">
                            ${fieldLabel}
                        </strong>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <div style="font-size: 11px; color: #666; margin-bottom: 5px;">CURRENT:</div>
                                <div style="
                                    padding: 10px;
                                    background: #f5f5f5;
                                    border-radius: 4px;
                                    font-size: 12px;
                                    max-height: 150px;
                                    overflow-y: auto;
                                    color: ${currentContent ? '#333' : '#999'};
                                ">
                                    ${currentContent || '(empty)'}
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 11px; color: #666; margin-bottom: 5px;">NEW (AI SUGGESTED):</div>
                                <div style="
                                    padding: 10px;
                                    background: #e8f5e9;
                                    border-radius: 4px;
                                    font-size: 12px;
                                    max-height: 150px;
                                    overflow-y: auto;
                                    color: #2e7d32;
                                ">
                                    ${escapeHtml(suggestedContent)}
                                </div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        `;
    }

    modalHtml += `
                </div>
                
                <div style="
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div style="background: #fff3cd; padding: 10px; border-radius: 4px; font-size: 12px; flex: 1; margin-right: 15px;">
                        ⚠️ Selected fields will be replaced with AI-generated content.
                    </div>
                    <div>
                        <button class="cancel-replacement" style="margin-right: 10px;">Cancel</button>
                        <button class="confirm-replacement" style="
                            background: #d32f2f;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Replace Selected</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    jQuery('body').append(modalHtml);

    console.log('[PL Coach] Modal shown with', fieldCount, 'fields');

    updateSelectionCount();
}

/**
 * Helper: Get human-readable field label
 */
function getFieldLabel(fieldId) {
    const labels = {
        'main_offer': 'Main Offer',
        'pricing_strategy': 'Pricing Strategy',
        'bonuses': 'Bonuses',
        'guarantee': 'Guarantee',
        'target_audience': 'Target Audience',
        'pain_points': 'Pain Points',
        'value_proposition': 'Value Proposition',
        'email_subject': 'Email Subject',
        'email_body': 'Email Body',
        'ad_headline': 'Ad Headline',
        'ad_body': 'Ad Body'
    };

    return labels[fieldId] || fieldId.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Helper: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Helper: Update selection count
 */
function updateSelectionCount() {
    const $modal = jQuery('.pl-field-replacement-modal');
    const total = $modal.find('.field-checkbox').length;
    const checked = $modal.find('.field-checkbox:checked').length;
    $modal.find('.selection-count').text(`${checked} of ${total} selected`);
}

// Modal interaction handlers
jQuery(document).on('click', '.close-modal, .cancel-replacement', function() {
    jQuery('.pl-field-replacement-modal').remove();
});

jQuery(document).on('click', '.select-all-modal-fields', function() {
    jQuery('.pl-field-replacement-modal .field-checkbox').prop('checked', true);
    updateSelectionCount();
});

jQuery(document).on('click', '.deselect-all-modal-fields', function() {
    jQuery('.pl-field-replacement-modal .field-checkbox').prop('checked', false);
    updateSelectionCount();
});

jQuery(document).on('change', '.pl-field-replacement-modal .field-checkbox', function() {
    updateSelectionCount();
});

jQuery(document).on('click', '.confirm-replacement', function() {
    const $modal = jQuery('.pl-field-replacement-modal');
    const $checkedBoxes = $modal.find('.field-checkbox:checked');

    if ($checkedBoxes.length === 0) {
        alert('Please select at least one field to replace.');
        return;
    }

    let replacedCount = 0;

    $checkedBoxes.each(function() {
        const $checkbox = jQuery(this);
        const fieldId = $checkbox.val();
        const suggestedContent = $checkbox.data('suggested');

        const $field = jQuery(`#${fieldId}, [name="${fieldId}"]`).first();

        if ($field.length) {
            $field.val(suggestedContent).trigger('change');

            $field.css('background', '#e8f5e9');
            setTimeout(() => {
                $field.css('background', '');
            }, 2000);

            replacedCount++;
            console.log(`[PL Coach] Replaced field: ${fieldId}`);
        } else {
            console.warn(`[PL Coach] Field not found: ${fieldId}`);
        }
    });

    $modal.remove();

    if (replacedCount > 0) {
        const $notice = jQuery(`
            <div style="
                position: fixed;
                top: 50px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 999999;
            ">
                ✓ Successfully updated ${replacedCount} field${replacedCount !== 1 ? 's' : ''}
            </div>
        `);

        jQuery('body').append($notice);
        setTimeout(() => $notice.fadeOut(() => $notice.remove()), 3000);
    }
});

// Initialize
jQuery(document).ready(function() {
    if (typeof productLaunch !== 'undefined') {
        window.productLaunchCoach = new EnhancedProductLaunchCoach();
    }
});

} // End file guard