(function ($) {
  if (typeof window.IS_ADMIN === 'undefined') {
    return;
  }

  const destRegistry = IS_ADMIN.destRegistry || {};
  const strings = IS_ADMIN.strings || {};

  function s(key, fallback) {
    return Object.prototype.hasOwnProperty.call(strings, key) ? strings[key] : fallback;
  }

  function openModal($el) {
    if (!$el || !$el.length) {
      return;
    }
    $el.removeAttr('hidden');
    $('body').addClass('is-modal-open');
  }

  function closeModal($el) {
    if (!$el || !$el.length) {
      return;
    }
    $el.attr('hidden', true);
    if ($('.is-modal:not([hidden])').length === 0) {
      $('body').removeClass('is-modal-open');
    }
  }

  function esc(value) {
    return String(value === undefined || value === null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function adminNotice(type, message) {
    const $area = $('#is-admin-notices');
    if (!$area.length) {
      return;
    }
    if (!type || !message) {
      $area.empty();
      return;
    }
    const classes = type === 'error'
      ? 'notice notice-error'
      : type === 'warning'
        ? 'notice notice-warning'
        : 'notice notice-success';
    $area.html(`<div class="${classes}"><p>${message}</p></div>`);
  }

  function buildFieldList() {
    const rows = [];
    Object.keys(destRegistry).forEach((phase) => {
      const keys = Array.isArray(destRegistry[phase]) ? destRegistry[phase] : [];
      if (!keys.length) {
        return;
      }
      const label = phase
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
      rows.push(`<div class="is-field-group"><h4>${esc(label)}</h4>`);
      keys.forEach((key) => {
        const cleanKey = esc(key);
        rows.push(
          `<label class="is-field">
            <input type="checkbox" class="is-field-check" value="${cleanKey}">
            <span>${cleanKey}</span>
          </label>`
        );
      });
      rows.push('</div>');
    });
    return rows.join('');
  }

  function renderPhasePrefill(report) {
    const prefills = report && report.phase_prefill ? report.phase_prefill : {};
    const items = [];

    Object.keys(prefills || {}).forEach((phase) => {
      const fields = prefills[phase];
      if (!fields || typeof fields !== 'object') {
        return;
      }
      Object.keys(fields).forEach((fieldKey) => {
        const value = fields[fieldKey];
        let display;
        if (Array.isArray(value)) {
          display = esc(JSON.stringify(value));
        } else if (value && typeof value === 'object') {
          display = esc(JSON.stringify(value));
        } else {
          display = esc(value);
        }
        items.push(`<li><code>${esc(phase)}.${esc(fieldKey)}</code>: ${display}</li>`);
      });
    });

    if (!items.length) {
      return '';
    }

    return `
      <div class="is-section">
        <h4>${esc(s('phasePrefillHeading', 'Phase Prefill'))}</h4>
        <ul>${items.join('')}</ul>
      </div>
    `;
  }

  function renderReport(report) {
    const score = report && report.score && report.score.overall ? report.score.overall : 0;
    const band = report && report.score && report.score.band ? report.score.band : '';
    const title = report && report.idea && report.idea.title ? report.idea.title : '';
    const niche = report && report.idea && report.idea.niche ? report.idea.niche : '';
    const market = report && report.market ? report.market : {};
    const competitors = report && Array.isArray(report.competitors) ? report.competitors : [];
    const outcomes = Array.isArray(market.outcomes)
      ? market.outcomes.map((item) => esc(item)).join(', ')
      : esc(market.outcomes || '');

    const competitorsHtml = competitors.length
      ? competitors
          .map((comp) => {
            const name = esc(comp && comp.name ? comp.name : '');
            const url = esc(comp && comp.url ? comp.url : '');
            return `<li>${name}${url ? ` — <a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>` : ''}</li>`;
          })
          .join('')
      : '<li>' + esc(s('competitorsEmpty', 'No competitors listed.')) + '</li>';

    return `
      <div class="is-report">
        <div class="is-report-head">
          <div class="is-score">${esc(score)}</div>
          <div>
            <h3>${esc(title)}</h3>
            <div class="is-meta">${esc(niche)}${band ? ' · ' + esc(band) : ''}</div>
          </div>
        </div>
        <div class="is-section">
          <h4>${esc(s('marketHeading', 'Market'))}</h4>
          <p><strong>${esc(s('audienceLabel', 'Audience:'))}</strong> ${esc(market.target_audience || '')}</p>
          <p><strong>${esc(s('problemLabel', 'Problem:'))}</strong> ${esc(market.core_problem || '')}</p>
          <p><strong>${esc(s('outcomesLabel', 'Outcomes:'))}</strong> ${outcomes}</p>
        </div>
        <div class="is-section">
          <h4>${esc(s('competitorsHeading', 'Competitors'))}</h4>
          <ul>${competitorsHtml}</ul>
        </div>
        ${renderPhasePrefill(report)}
      </div>
    `;
  }

  function renderMapperResults(problems) {
    const $container = $('#is-mapper-results');
    if (!$container.length) {
      return;
    }

    if (!problems || !problems.length) {
      const message = esc(s('mapperCheckSuccess', 'All mapping keys look good!'));
      $container.html(`<div class="notice notice-success"><p>${message}</p></div>`).removeAttr('hidden');
      return;
    }

    const statusLabels = {
      not_set_yet: { icon: '⚠️', label: esc(s('statusNotSet', 'Option not yet created (will be set on first push)')), className: 'warning' },
      empty_or_invalid_key: { icon: '❌', label: esc(s('statusInvalidKey', 'Invalid destination key')), className: 'error' },
      map_pair_missing: { icon: '❌', label: esc(s('statusMissingPair', 'Mapping pair is incomplete')), className: 'error' },
    };

    const rows = problems.map((problem) => {
      const meta = statusLabels[problem.issue] || { icon: '⚠️', label: esc(problem.issue || 'Issue'), className: 'warning' };
      return `
        <tr class="status-${meta.className}">
          <td>${esc(problem.phase || '')}</td>
          <td>${esc(problem.key || '')}</td>
          <td>${meta.icon} ${meta.label}</td>
        </tr>
      `;
    }).join('');

    $container
      .html(`
        <table class="widefat">
          <thead>
            <tr>
              <th>${esc(s('phaseColumn', 'Phase'))}</th>
              <th>${esc(s('destKeyColumn', 'Destination Key'))}</th>
              <th>${esc(s('statusColumn', 'Status'))}</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      `)
      .removeAttr('hidden');
  }

  $(document).on('click', '.is-view-report', async function (event) {
    event.preventDefault();
    const ideaId = $(this).data('id');
    if (!ideaId) {
      adminNotice('error', esc(s('missingIdeaId', 'Idea identifier is missing.')));
      return;
    }

    const $modal = $('#is-report-modal');
    if (!$modal.length) {
      return;
    }
    $modal.find('.is-modal-inner').html('<p>' + esc(s('loadingText', 'Loading…')) + '</p>');
    openModal($modal);

    try {
      const response = await $.get(IS_ADMIN.ajax, {
        action: 'is_admin_get_report',
        idea_id: ideaId,
        nonce: IS_ADMIN.nonce
      });

      if (!response || !response.success) {
        throw new Error(response && response.data && response.data.message ? response.data.message : s('loadReportError', 'Failed to load report.'));
      }

      $modal.find('.is-modal-inner').html(renderReport(response.data.report));
    } catch (error) {
      const message = error && error.message ? error.message : s('loadReportError', 'Failed to load report.');
      adminNotice('error', esc(message));
      $modal.find('.is-modal-inner').html('<p class="error">' + esc(message) + '</p>');
    }
  });

  $(document).on('click', '.is-push-open', function (event) {
    event.preventDefault();
    const ideaId = $(this).data('id');
    if (!ideaId) {
      adminNotice('error', esc(s('missingIdeaId', 'Idea identifier is missing.')));
      return;
    }
    const $modal = $('#is-push-modal');
    if (!$modal.length) {
      return;
    }
    $modal.data('ideaId', ideaId);
    $modal.find('#is-field-list').html(buildFieldList());
    $modal.find('#is-overwrite').prop('checked', false);
    openModal($modal);
  });

  $(document).on('click', '#is-push-modal .is-apply', async function () {
    const $modal = $('#is-push-modal');
    if (!$modal.length) {
      return;
    }
    const ideaId = $modal.data('ideaId');
    const overwrite = $modal.find('#is-overwrite').is(':checked') ? 1 : 0;
    const selected = $modal.find('.is-field-check:checked').map(function () {
      return $(this).val();
    }).get();

    if (!ideaId) {
      adminNotice('error', esc(s('missingIdeaId', 'Idea identifier is missing.')));
      return;
    }

    if (!selected.length) {
      adminNotice('warning', esc(s('selectAtLeastOne', 'Select at least one destination field.')));
      return;
    }

    try {
      const response = await $.post(IS_ADMIN.ajax, {
        action: 'is_admin_push_to_phases',
        idea_id: ideaId,
        selected: selected,
        overwrite: overwrite,
        nonce: IS_ADMIN.nonce
      });

      if (!response || !response.success) {
        throw new Error(response && response.data && response.data.message ? response.data.message : s('applyMappingError', 'Failed to apply mapping.'));
      }

      adminNotice('success', esc(s('applyMappingSuccess', 'Report data applied to launch phases.')));
      closeModal($modal);
    } catch (error) {
      const message = error && error.message ? error.message : s('applyMappingError', 'Failed to apply mapping.');
      adminNotice('error', esc(message));
    }
  });

  $(document).on('click', '.is-modal .is-cancel', function () {
    closeModal($(this).closest('.is-modal'));
  });

  $(document).on('click', '.is-modal', function (event) {
    if ($(event.target).is('.is-modal')) {
      closeModal($(this));
    }
  });

  $(document).on('submit', '#is-validate-form', async function (event) {
    event.preventDefault();
    const $result = $('#is-validate-result');
    if ($result.length) {
      $result.removeAttr('hidden').html('<p>' + esc(s('validatingText', 'Validating…')) + '</p>');
    }

    const formData = Object.fromEntries(new FormData(this).entries());
    formData.action = 'is_admin_validate_idea';
    formData.nonce = IS_ADMIN.nonce;

    try {
      const response = await $.post(IS_ADMIN.ajax, formData);
      if (!response || !response.success) {
        throw new Error(response && response.data && response.data.message ? response.data.message : s('validateError', 'Validation failed.'));
      }

      const ideaId = response.data.idea_id;
      const report = response.data.report;

      const pushButton = `<p><button type="button" class="button button-primary is-push-open" data-id="${esc(ideaId)}">${esc(s('pushButton', 'Push to 8 Phases'))}</button></p>`;

      if ($result.length) {
        $result.html(renderReport(report) + pushButton);
      }

      adminNotice('success', esc(s('validateSuccess', 'Idea validated successfully.')));
    } catch (error) {
      const message = error && error.message ? error.message : s('validateError', 'Validation failed.');
      adminNotice('error', esc(message));
      if ($result.length) {
        $result.html('<p class="error">' + esc(s('validateErrorPrefix', 'Validation failed: ')) + esc(error.message || '') + '</p>');
      }
    }
  });

  $(document).on('click', '#is-mapper-check', async function (event) {
    event.preventDefault();
    const $button = $(this);
    const originalText = $button.text();
    $button.prop('disabled', true).text(esc(s('checkingText', 'Checking…')));
    adminNotice(null, null);

    try {
      const response = await $.post(IS_ADMIN.ajax, {
        action: 'is_admin_mapper_check',
        nonce: IS_ADMIN.nonce
      });

      if (!response || !response.success) {
        throw new Error(response && response.data && response.data.message ? response.data.message : s('mapperCheckError', 'Mapper check failed.'));
      }

      renderMapperResults(response.data.problems || []);
      adminNotice('success', esc(s('mapperCheckSuccessNotice', 'Mapper check complete.')));
    } catch (error) {
      const message = error && error.message ? error.message : s('mapperCheckError', 'Mapper check failed.');
      adminNotice('error', esc(message));
    } finally {
      $button.prop('disabled', false).text(originalText);
    }
  });
})(jQuery);
