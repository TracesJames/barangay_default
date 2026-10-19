<?php
/**
 * Modal + script for Nutritional Status Breakdown chips.
 * Expects optional $nutritionStatusChildrenCityWide (bool) — show barangay column.
 */
$nutritionStatusChildrenCityWide = !empty($nutritionStatusChildrenCityWide);
$nutritionStatusChildrenScope = $nutritionStatusChildrenCityWide ? 'city' : 'barangay';
?>
<div class="modal fade" id="nutritionStatusChildrenModal" tabindex="-1" role="dialog" aria-labelledby="nutritionStatusChildrenTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content bg-dark">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="nutritionStatusChildrenTitle">
          <i class="fas fa-child mr-2"></i><span id="nutritionStatusChildrenLabel">Children</span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3" id="nutritionStatusChildrenSummary">Loading…</p>
        <div class="table-responsive">
          <table class="table table-sm table-dark table-striped mb-0" id="nutritionStatusChildrenTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Child name</th>
                <th>Age</th>
                <?php if ($nutritionStatusChildrenCityWide) : ?>
                <th>Barangay</th>
                <?php endif; ?>
                <th>Purok / Household</th>
                <th>Source</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="<?= $nutritionStatusChildrenCityWide ? 6 : 5 ?>" class="text-center text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
(function ($) {
  var cityWide = <?= $nutritionStatusChildrenCityWide ? 'true' : 'false' ?>;
  var listScope = <?= json_encode($nutritionStatusChildrenScope, JSON_UNESCAPED_UNICODE) ?>;

  function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
  }

  function formatDate(raw) {
    var s = String(raw || '').trim();
    if (!s) return '—';
    var t = Date.parse(s);
    if (isNaN(t)) return escapeHtml(s);
    var d = new Date(t);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function renderRows(children) {
    var $tbody = $('#nutritionStatusChildrenTable tbody');
    $tbody.empty();
    if (!children || !children.length) {
      var cols = cityWide ? 6 : 5;
      $tbody.append('<tr><td colspan="' + cols + '" class="text-center text-muted py-4">No children registered with this status.</td></tr>');
      return;
    }
    children.forEach(function (row, i) {
      var place = [];
      if (row.purok) place.push(row.purok);
      if (row.household) place.push('HH: ' + row.household);
      var placeHtml = place.length ? escapeHtml(place.join(' · ')) : '—';
      var html = '<tr>'
        + '<td>' + (i + 1) + '</td>'
        + '<td><strong>' + escapeHtml(row.name) + '</strong><br><small class="text-muted">' + formatDate(row.date) + '</small></td>'
        + '<td>' + escapeHtml(row.age_label || '—') + '</td>';
      if (cityWide) {
        html += '<td>' + escapeHtml(row.barangay || '—') + '</td>';
      }
      html += '<td>' + placeHtml + '</td>'
        + '<td><small>' + escapeHtml(row.source || '') + '</small></td>'
        + '</tr>';
      $tbody.append(html);
    });
  }

  $(document).on('click', '.nutrition-status-chip[data-status]', function (e) {
    e.preventDefault();
    var status = String($(this).data('status') || '');
    var label = String($(this).data('label') || status);
    if (!status) return;

    $('#nutritionStatusChildrenLabel').text(label);
    $('#nutritionStatusChildrenSummary').text('Loading children…');
    renderRows([]);
    $('#nutritionStatusChildrenModal').modal('show');

    var params = { status: status };
    if (listScope === 'city') {
      params.scope = 'city';
    }
    $.getJSON('nutritionStatusChildren.php', params)
      .done(function (res) {
        if (!res || !res.ok) {
          $('#nutritionStatusChildrenSummary').text((res && res.error) ? res.error : 'Could not load children.');
          return;
        }
        var count = res.count || 0;
        $('#nutritionStatusChildrenLabel').text(res.label || label);
        $('#nutritionStatusChildrenSummary').text(
          count === 0
            ? 'No children currently listed under ' + (res.label || label) + '.'
            : count + ' child' + (count === 1 ? '' : 'ren') + ' with status: ' + (res.label || label)
        );
        renderRows(res.children || []);
      })
      .fail(function (xhr) {
        var msg = 'Could not load children.';
        try {
          var data = JSON.parse(xhr.responseText);
          if (data && data.error) msg = data.error;
        } catch (err) {}
        $('#nutritionStatusChildrenSummary').text(msg);
      });
  });
})(jQuery);
</script>
