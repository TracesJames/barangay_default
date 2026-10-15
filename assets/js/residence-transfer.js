/**
 * Resident transfer UI for the All Residence page (admin and secretary portals).
 * Expects jQuery, Bootstrap modals, SweetAlert2 and barangay-ui.js on the page.
 */
(function ($) {
  'use strict';

  function reloadResidenceTable() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#allResidenceTable')) {
      $('#allResidenceTable').DataTable().ajax.reload(null, false);
    }
  }

  function transferError(xhr) {
    var message = '';
    try {
      message = (JSON.parse(xhr.responseText || '{}').error || '');
    } catch (e) {
      message = '';
    }
    if (message === '' && xhr && xhr.status >= 400 && xhr.status < 500 && xhr.responseText
      && xhr.responseText.trim().charAt(0) !== '<') {
      message = xhr.responseText.trim();
    }
    if (message !== '' && typeof Swal !== 'undefined') {
      Swal.fire({ title: 'Transfer not saved', text: message, type: 'error', width: '440px' });
      return;
    }
    if (typeof barangayAjaxError === 'function') {
      barangayAjaxError(xhr);
    }
  }

  function showModalHtml(html, modalId) {
    $('#' + modalId).modal('hide').remove();
    $('#residenceTransferHost').remove();
    $('<div id="residenceTransferHost"></div>').html(html).appendTo('body');
    $('#' + modalId).modal('show');
  }

  function refreshBadge() {
    var $badge = $('#residenceTransfersBadge');
    if (!$badge.length) {
      return;
    }
    $.ajax({ url: 'residenceTransferPanel.php', type: 'POST', dataType: 'html', cache: false })
      .done(function (html) {
        var total = $('<div>').html(html).find('#transfersIncoming tbody tr').length;
        $badge.text(total).toggleClass('d-none', total === 0);
      });
  }

  function openPanel() {
    $.ajax({ url: 'residenceTransferPanel.php', type: 'POST', dataType: 'html', cache: false })
      .done(function (html) { showModalHtml(html, 'residenceTransfersModal'); })
      .fail(transferError);
  }

  $(document).on('click', '.transferResidence', function () {
    var residenceId = $(this).attr('data-residence');
    $.ajax({
      url: 'residenceTransferForm.php',
      type: 'POST',
      dataType: 'html',
      cache: false,
      data: { residence_id: residenceId }
    }).done(function (html) {
      showModalHtml(html, 'residenceTransferModal');
    }).fail(transferError);
  });

  $(document).on('input', '#transferMemberFilter', function () {
    var term = $(this).val().toLowerCase().trim();
    $('#transferMemberList .transfer-member-item').each(function () {
      $(this).toggle(term === '' || String($(this).data('search')).indexOf(term) !== -1);
    });
  });

  $(document).on('submit', '#residenceTransferForm', function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submit = $('#residenceTransferSubmit');
    var destination = $form.find('#transferToBarangay option:selected').text();
    var members = $form.find('input[name="member_ids[]"]:checked').length;

    Swal.fire({
      title: 'Transfer to ' + destination + '?',
      html: (members > 0 ? '<b>' + (members + 1) + ' residents</b> will be transferred.<br>' : '')
        + 'The current record stays here as history.',
      type: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, continue',
      width: '420px'
    }).then(function (result) {
      if (!result.value) {
        return;
      }
      $submit.prop('disabled', true);
      $.ajax({
        url: 'residenceTransferAction.php',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: $form.serialize()
      }).done(function (response) {
        $('#residenceTransferModal').modal('hide');
        Swal.fire({ title: 'Done', text: response.message, type: 'success', width: '440px' });
        reloadResidenceTable();
        refreshBadge();
      }).fail(transferError).always(function () {
        $submit.prop('disabled', false);
      });
    });
  });

  $(document).on('click', '#openResidenceTransfers', openPanel);

  $(document).on('click', '.residence-transfer-decide', function () {
    var action = $(this).attr('data-action');
    var transferId = $(this).attr('data-transfer');
    var prompts = {
      approve: { title: 'Approve this transfer?', text: 'New resident records will be created in this barangay.', button: 'Approve' },
      reject: { title: 'Reject this transfer?', text: 'The residents stay in their current barangay.', button: 'Reject' },
      cancel: { title: 'Cancel this request?', text: 'The residents stay in this barangay.', button: 'Cancel request' }
    };
    var prompt = prompts[action];
    if (!prompt) {
      return;
    }
    var options = {
      title: prompt.title,
      text: prompt.text,
      type: action === 'approve' ? 'question' : 'warning',
      showCancelButton: true,
      confirmButtonText: prompt.button,
      cancelButtonText: 'Back',
      width: '440px'
    };
    if (action !== 'approve') {
      options.input = 'text';
      options.inputPlaceholder = 'Note (optional)';
    }
    Swal.fire(options).then(function (result) {
      if (result.dismiss || result.value === undefined) {
        return;
      }
      $.ajax({
        url: 'residenceTransferAction.php',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: { action: action, transfer_id: transferId, note: typeof result.value === 'string' ? result.value : '' }
      }).done(function (response) {
        $('#residenceTransfersModal').modal('hide');
        Swal.fire({ title: 'Done', text: response.message, type: 'success', width: '440px' }).then(openPanel);
        reloadResidenceTable();
        refreshBadge();
      }).fail(transferError);
    });
  });
})(jQuery);
