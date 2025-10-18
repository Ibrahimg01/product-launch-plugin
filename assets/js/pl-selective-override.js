/*! PL Selective Field Replacement Override - v2.3.56 (Field-Specific Replacements) */
(function () {
  if (typeof window === 'undefined') { return; }
  var $ = window.jQuery;
  // --- Enhanced Modal Markup Builder with Before/After Preview ---
  function pl_buildSelectiveModalHtml(fields) {
    var total = fields.length;
    var coach = window.productLaunchCoach;
    console.log('[PL Selective] Building modal for', total, 'fields');
    console.log('[PL Selective] Pending generated content:', coach.pendingGeneratedContent);

    var html = '' +
    '<div class="pl-selective-overlay" role="dialog" aria-modal="true">' +
      '<div class="pl-selective-dialog">' +
        '<div class="pl-selective-header">' +
            '<div class="pl-title">Choose Fields to Replace</div>' +
            '<button type="button" class="pl-close" data-action="cancel" aria-label="Close">×</button>' +
        '</div>' +
        '<div class="pl-selective-subhead">' +
            '<button type="button" class="pl-btn small" data-action="select-all">Select All</button> ' +
            '<button type="button" class="pl-btn small ghost" data-action="deselect-all">Deselect All</button> ' +
            '<span class="pl-count" data-selected-count>0 of ' + String(total) + ' selected</span>' +
        '</div>' +
        '<div class="pl-list">';

    var escapeHtml = function(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    };

    for (var i=0;i<fields.length;i++) {
      var fieldId = fields[i];
      var fieldInfo = coach.formContext.get(fieldId) || {};
      var name = fieldInfo.label || fieldId;

      // CRITICAL FIX: Always read from DOM, never from cache
      var currentValue = '';

      // Try multiple methods to get the actual DOM value
      if (fieldInfo.element && typeof fieldInfo.element.val === 'function') {
        currentValue = String(fieldInfo.element.val() || '').trim();
      }

      // Fallback 1: Direct jQuery by ID
      if (!currentValue && fieldId) {
        try {
          var $byId = jQuery('#' + fieldId);
          if ($byId.length) {
            currentValue = String($byId.val() || '').trim();
          }
        } catch(e) {}
      }

      // Fallback 2: jQuery by name attribute
      if (!currentValue && fieldId) {
        try {
          var $byName = jQuery('[name="' + fieldId + '"]');
          if ($byName.length) {
            currentValue = String($byName.val() || '').trim();
          }
        } catch(e) {}
      }

      // Fallback 3: Look for textarea/input with matching class and id/name
      if (!currentValue && name) {
        try {
          var $byClass = jQuery('textarea.ai-fillable, input.ai-fillable').filter(function() {
            var id = jQuery(this).attr('id');
            var nm = jQuery(this).attr('name');
            return id === fieldId || nm === fieldId;
          });
          if ($byClass.length) {
            currentValue = String($byClass.first().val() || '').trim();
          }
        } catch(e) {}
      }

      console.log('[PL Selective] Field:', name, '- Current value:', (currentValue || '').substring(0, 100));

      // Get the NEW value from pre-generated content first
      var newValue = '';
      if (coach.pendingGeneratedContent && coach.pendingGeneratedContent[fieldId]) {
        newValue = String(coach.pendingGeneratedContent[fieldId]).trim();
        console.log('[PL Selective] ✓ Found pre-generated content for', name);
      } else {
        console.warn('[PL Selective] ✗ No pre-generated content for', name);

        // Fallback to extraction attempt if no pre-generated content
        if (coach.currentAnalysis) {
          if (typeof coach.currentAnalysis === 'string') {
            newValue = extractFieldContent(coach.currentAnalysis, name, fieldId);
            if (newValue) {
              console.log('[PL Selective] ✓ Extracted from analysis for', name);
            }
          }
        }
      }

      if (!newValue) {
        newValue = 'AI-generated content will appear here';
        console.warn('[PL Selective] Using placeholder for', name);
      }

      // Create expandable preview
      var currentPreview = currentValue.length > 80 ? currentValue.substr(0, 80) + '...' : currentValue;
      var newPreview = newValue.length > 80 ? newValue.substr(0, 80) + '...' : newValue;

      // Format AI markers before displaying
      var formatMarkers = function(str) {
        return str
          .replace(/⟦b⟧/g, '<strong>')
          .replace(/⟦\/b⟧/g, '</strong>')
          .replace(/⟦i⟧/g, '<em>')
          .replace(/⟦\/i⟧/g, '</em>');
      };

      // Display logic - show "Empty" only if truly empty
      var currentDisplay = currentValue ? formatMarkers(currentPreview.replace(/</g,'&lt;')) : '<em>Empty</em>';
      var newDisplay = newValue ? formatMarkers(newPreview.replace(/</g,'&lt;')) : '<em>AI-generated</em>';

      html += '' +
        '<div class="pl-item-wrapper field-selection-item" data-field-id="' + String(fieldId).replace(/\"/g,'&quot;') + '">' +
          '<label class="pl-item">' +
            '<input type="checkbox" class="pl-check" value="' + String(fieldId).replace(/\"/g,'&quot;') + '" data-field-id="' + String(fieldId).replace(/\"/g,'&quot;') + '" checked> ' +
            '<div class="pl-item-content">' +
              '<div class="pl-item-name">' + String(name).replace(/</g,'&lt;') + '</div>' +
              '<div class="pl-change-preview">' +
                '<div class="pl-before">' +
                  '<span class="pl-label">Current:</span> ' +
                  '<span class="pl-value">' + currentDisplay + '</span>' +
                '</div>' +
                '<div class="pl-arrow">→</div>' +
                '<div class="pl-after">' +
                  '<span class="pl-label">New:</span> ' +
                  '<span class="pl-value pl-new-value">' + newDisplay + '</span>' +
                '</div>' +
              '</div>';

      // Add expand button if content is long
      if (currentValue.length > 80 || newValue.length > 80) {
        html += '<button class="pl-expand-btn" data-field-id="' + String(fieldId).replace(/\"/g,'&quot;') + '">Show Full Content</button>';
      }

      html += '' +
              '<div class="pl-hidden-values" aria-hidden="true" style="display:none;">' +
                '<span class="current-value">' + escapeHtml(currentValue || '') + '</span>' +
                '<span class="new-value">' + escapeHtml(newValue || '') + '</span>' +
              '</div>' +
            '</div>' +
          '</label>' +
        '</div>';
    }

    html += '' +
        '</div>' +
        '<div class="pl-warning">⚠ Selected fields will be replaced with AI-generated content.</div>' +
        '<div class="pl-actions">' +
            '<button type="button" class="pl-btn ghost" data-action="cancel">Cancel</button>' +
            '<button type="button" class="pl-btn primary replace-selected-fields" disabled>Replace Selected</button>' +
        '</div>' +
      '</div>' +
    '</div>';

    return html;
  }

  // Helper function to extract field content from analysis text (fallback only)
  function extractFieldContent(analysisText, fieldName, fieldId) {
    if (!analysisText) return '';
    if (!fieldName) return '';
    function escapeRegex(str) {
      return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    var escapedName = escapeRegex(fieldName);
    var patterns = [
      new RegExp('\\*\\*' + escapedName + '\\*\\*:?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'i'),
      new RegExp('^' + escapedName + ':?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'im'),
      new RegExp('\\d+\\.\\s*\\*\\*' + escapedName + '\\*\\*:?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'i'),
      new RegExp('\\*\\*' + escapeRegex(String(fieldName).replace(/_/g, ' ')) + '\\*\\*:?\\s*([^\\n]+)', 'i')
    ];

    for (var i = 0; i < patterns.length; i++) {
      try {
        var match = String(analysisText).match(patterns[i]);
        if (match && match[1]) {
          return match[1].trim();
        }
      } catch(e) {
        if (window.console) console.warn('[PL Selective] Pattern ' + i + ' failed for field:', fieldName, e);
      }
    }
    return '';
  }

  // --- Handle Expand Button Clicks ---
  function handleExpandClick(btn) {
    if (!btn || !btn.classList || !btn.classList.contains('pl-expand-btn')) return;
    var fieldId = btn.getAttribute('data-field-id');
    var coach = window.productLaunchCoach;
    var fieldInfo = coach.formContext.get(fieldId) || {};
    var name = fieldInfo.label || fieldId;

    // CRITICAL FIX: Read from DOM, not cache
    var currentValue = '';
    if (fieldInfo.element && typeof fieldInfo.element.val === 'function') {
      currentValue = String(fieldInfo.element.val() || '').trim();
    } else {
      try {
        var $field = jQuery('#' + fieldId);
        if ($field.length) {
          currentValue = String($field.val() || '').trim();
        }
      } catch(e) {}
    }

    // Get new value from pre-generated content first
    var newValue = '';
    if (coach.pendingGeneratedContent && coach.pendingGeneratedContent[fieldId]) {
      newValue = String(coach.pendingGeneratedContent[fieldId]).trim();
    } else if (coach.currentAnalysis) {
      if (typeof coach.currentAnalysis === 'string') {
        function escapeRegex(str) { return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
        var escapedName = escapeRegex(name);
        var patterns = [
          new RegExp('\\*\\*' + escapedName + '\\*\\*:?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'i'),
          new RegExp('^' + escapedName + ':?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'im'),
          new RegExp('\\d+\\.\\s*\\*\\*' + escapedName + '\\*\\*:?\\s*([^\\n]+(?:\\n(?!\\d+\\.|\\*\\*)[^\\n]+)*)', 'i'),
          new RegExp('\\*\\*' + escapeRegex(String(name).replace(/_/g, ' ')) + '\\*\\*:?\\s*([^\\n]+)', 'i')
        ];
        for (var i=0;i<patterns.length;i++){ 
          try{ 
            var m=String(coach.currentAnalysis).match(patterns[i]); 
            if(m&&m[1]){ 
              newValue=m[1].trim(); 
              break; 
            } 
          }catch(e){} 
        }
      }
    }

    if (!newValue) {
      newValue = 'AI-generated content';
    }

    var item = btn.closest('.pl-item-wrapper');
    if (!item) return;
    var changePreview = item.querySelector('.pl-change-preview');
    if (!changePreview) return;

    if (btn.textContent === 'Show Full Content') {
      changePreview.innerHTML = '' +
        '<div class="pl-before pl-expanded">' +
          '<span class="pl-label">Current:</span><br>' +
          '<div class="pl-value">' + (currentValue || '<em>Empty</em>').replace(/</g,'&lt;').replace(/\n/g, '<br>') + '</div>' +
        '</div>' +
        '<div class="pl-arrow">→</div>' +
        '<div class="pl-after pl-expanded">' +
          '<span class="pl-label">New:</span><br>' +
          '<div class="pl-value pl-new-value">' + (newValue || '<em>AI-generated</em>').replace(/</g,'&lt;').replace(/\n/g, '<br>') + '</div>' +
        '</div>';
      btn.textContent = 'Show Less';
    } else {
      var currentPreview = currentValue.length > 80 ? currentValue.substr(0, 80) + '...' : currentValue;
      var newPreview = newValue.length > 80 ? newValue.substr(0, 80) + '...' : newValue;

      changePreview.innerHTML = '' +
        '<div class="pl-before">' +
          '<span class="pl-label">Current:</span> ' +
          '<span class="pl-value">' + (currentPreview || '<em>Empty</em>').replace(/</g,'&lt;') + '</span>' +
        '</div>' +
        '<div class="pl-arrow">→</div>' +
        '<div class="pl-after">' +
          '<span class="pl-label">New:</span> ' +
          '<span class="pl-value pl-new-value">' + (newPreview || '<em>AI-generated</em>').replace(/</g,'&lt;') + '</span>' +
        '</div>';
      btn.textContent = 'Show Full Content';
    }
  }

  // --- Main Override Method ---
  function showSelectiveOverrideConfirmation(filledFields) {
    try {
      if (!filledFields || !filledFields.length) {
        alert('No fields detected to replace.');
        return;
      }

      var coach = window.productLaunchCoach;

      console.log('[PL Selective] showSelectiveOverrideConfirmation called');
      console.log('[PL Selective]   - Fields to fill:', filledFields);
      console.log('[PL Selective]   - pendingGeneratedContent:', coach && coach.pendingGeneratedContent);
      console.log('[PL Selective]   - currentAnalysis exists:', !!(coach && coach.currentAnalysis));

      if (!coach || !coach.pendingGeneratedContent || !Object.keys(coach.pendingGeneratedContent).length) {
        console.error('[PL Selective] ✗ No pendingGeneratedContent available!');
        alert('No generated content available. Please try "Generate Improved Content" again.');
        return;
      }

      var modalId = 'pl-selective-replace-modal';
      var prev = document.getElementById(modalId);
      if (prev && prev.parentNode) { prev.parentNode.removeChild(prev); }

      var wrap = document.createElement('div');
      wrap.id = modalId;
      wrap.className = 'pl-selective-root';
      wrap.innerHTML = pl_buildSelectiveModalHtml(filledFields);
      document.body.appendChild(wrap);

      var overlay = wrap.querySelector('.pl-selective-overlay');
      if (!overlay) {
        console.error('[PL Selective] Overlay not found');
        return;
      }

      var checkboxes = overlay.querySelectorAll('input[type="checkbox"][data-field-id]');
      var countEl = overlay.querySelector('[data-selected-count]');
      var replaceBtn = overlay.querySelector('.replace-selected-fields');

      function updateCount() {
        var selected = 0, total = 0;
        for (var i=0;i<checkboxes.length;i++){ total++; if (checkboxes[i].checked){ selected++; } }
        if (countEl){ countEl.textContent = String(selected) + ' of ' + String(total) + ' selected'; }
        if (replaceBtn){ replaceBtn.disabled = (selected === 0); }
      }
      updateCount();

      // Clicks
      overlay.addEventListener('click', function(e){
        var t = e.target || e.srcElement;
        if (!t) { return; }

        // Expand
        if (t.classList && t.classList.contains('pl-expand-btn')) {
          e.preventDefault(); e.stopPropagation(); handleExpandClick(t); return;
        }

        // Cancel / close
        var isCancel = t.getAttribute('data-action') === 'cancel' ||
                      (t.className && String(t.className).indexOf('pl-close') !== -1);
        if (isCancel) {
          if (wrap && wrap.parentNode) { wrap.parentNode.removeChild(wrap); }
          return;
        }

        // Select all / Deselect all
        if (t.getAttribute('data-action') === 'select-all') {
          for (var i=0;i<checkboxes.length;i++){ checkboxes[i].checked = true; }
          updateCount();
          return;
        }
        if (t.getAttribute('data-action') === 'deselect-all') {
          for (var j=0;j<checkboxes.length;j++){ checkboxes[j].checked = false; }
          updateCount();
          return;
        }

        // Replace
      });

      // Change updates
      overlay.addEventListener('change', function(e){
        var t = e.target || e.srcElement;
        if (t && t.className && String(t.className).indexOf('pl-check') !== -1) {
          updateCount();
        }
      });

    } catch (err) {
      console.error('[PL Selective] Error:', err);
      alert('Unable to open selective replacement dialog.');
    }
  }

  // --- Wait for INSTANCE ---
  function installOverride() {
    if (!window.productLaunchCoach) {
      setTimeout(installOverride, 100);
      return;
    }
    var coach = window.productLaunchCoach;
    var original = coach.showOverrideConfirmation;
    coach.showOverrideConfirmation = function(filledFields, generatedContent) {
      console.log('[PL Selective] ✓ Intercepted! Showing selective modal');
      console.log('[PL Selective]   - Fields:', filledFields);
      console.log('[PL Selective]   - Generated content provided:', !!generatedContent);
      if (generatedContent) {
        this.pendingGeneratedContent = generatedContent;
        console.log('[PL Selective] ✓ Stored pendingGeneratedContent');
      } else {
        console.warn('[PL Selective] ✗ No generated content provided to override method');
      }
      showSelectiveOverrideConfirmation.call(this, filledFields);
    };
    console.log('[PL Selective] ✓ Override installed successfully');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installOverride);
  } else {
    installOverride();
  }

  if ($ && typeof $.fn === 'object') {
    $(document).on('click', '.replace-selected-fields', function(e) {
      e.preventDefault();

      var $button = $(this);
      var $modal = $button.closest('.pl-selective-modal');
      if ($modal.length === 0) {
        $modal = $button.closest('.pl-selective-overlay');
      }
      var selectedFields = [];

      $modal.find('input[type="checkbox"]:checked').each(function() {
        var $checkbox = $(this);
        var fieldId = $checkbox.val() || $checkbox.attr('data-field-id');
        var $fieldContainer = $checkbox.closest('.field-selection-item');
        var currentValue = $fieldContainer.find('.current-value').text();
        var newValue = $fieldContainer.find('.new-value').text();
        var fieldLabel = $fieldContainer.find('label').first().text().replace(/^\s*\n\s*/, '');

        selectedFields.push({
          id: fieldId,
          label: fieldLabel,
          current: currentValue,
          suggested: newValue,
          element: $('[name="' + fieldId + '"], [id="' + fieldId + '"]').first()
        });
      });

      if (selectedFields.length === 0) {
        alert('Please select at least one field to replace.');
        return;
      }

      $button.prop('disabled', true).text('Replacing fields...');

      var successCount = 0;
      var errorFields = [];

      selectedFields.forEach(function(field) {
        try {
          var $targetField = field.element;

          if (!$targetField || $targetField.length === 0) {
            console.warn('Field not found:', field.id);
            errorFields.push(field.label);
            return;
          }

          if ($targetField.is('textarea')) {
            $targetField.val(field.suggested).trigger('change');
          } else if ($targetField.is('input[type="text"], input[type="search"], input[type="url"], input[type="email"], input[type="tel"]')) {
            $targetField.val(field.suggested).trigger('change');
          } else if ($targetField.is('select')) {
            $targetField.val(field.suggested).trigger('change');
          } else if ($targetField.prop('contentEditable') === 'true') {
            $targetField.html(field.suggested).trigger('input');
          } else {
            $targetField.val(field.suggested).trigger('change');
          }

          $targetField.addClass('field-just-updated');
          setTimeout(function() {
            $targetField.removeClass('field-just-updated');
          }, 2000);

          successCount++;
        } catch (error) {
          console.error('Error updating field:', field.id, error);
          errorFields.push(field.label);
        }
      });

      var $overlay = $button.closest('.pl-selective-overlay');
      if ($overlay.length) {
        $overlay.fadeOut(300, function() {
          $(this).remove();
        });
      }

      if (errorFields.length > 0) {
        alert('Updated ' + successCount + ' fields successfully.\n\nFailed to update: ' + errorFields.join(', '));
      } else {
        showSuccessNotification('Successfully updated ' + successCount + ' field' + (successCount !== 1 ? 's' : ''));
      }
    });
  }

  function showSuccessNotification(message) {
    var $ = window.jQuery;
    if (!$ || typeof $.fn !== 'object') {
      alert(message);
      return;
    }

    var $notification = $('<div class="pl-success-notification">' + message + '</div>');
    $('body').append($notification);

    setTimeout(function() {
      $notification.addClass('show');
    }, 100);

    setTimeout(function() {
      $notification.removeClass('show');
      setTimeout(function() {
        $notification.remove();
      }, 300);
    }, 3000);
  }

  if (!document.getElementById('pl-selective-style')) {
    var style = document.createElement('style');
    style.id = 'pl-selective-style';
    style.textContent = '\n    .field-just-updated {\n        animation: fieldUpdatePulse 0.5s ease-in-out;\n        border-color: #46b450 !important;\n        box-shadow: 0 0 0 1px #46b450 !important;\n    }\n    \n    @keyframes fieldUpdatePulse {\n        0%, 100% { \n            transform: scale(1); \n            opacity: 1;\n        }\n        50% { \n            transform: scale(1.02); \n            opacity: 0.9;\n        }\n    }\n    \n    .pl-success-notification {\n        position: fixed;\n        top: 32px;\n        right: 20px;\n        background: #46b450;\n        color: white;\n        padding: 15px 20px;\n        border-radius: 4px;\n        box-shadow: 0 2px 10px rgba(0,0,0,0.2);\n        z-index: 999999;\n        opacity: 0;\n        transform: translateY(-20px);\n        transition: all 0.3s ease;\n    }\n    \n    .pl-success-notification.show {\n        opacity: 1;\n        transform: translateY(0);\n    }\n  ';
    document.head.appendChild(style);
  }
})();
