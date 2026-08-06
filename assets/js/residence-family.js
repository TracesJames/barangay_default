(function ($) {
  function bindDependentRows($scope) {
    $scope.find('.remove-dependent-row').off('click.residenceFamily').on('click.residenceFamily', function () {
      var $list = $(this).closest('#dependents-list');
      var $rows = $list.find('.dependent-row');
      if ($rows.length <= 1) {
        $rows.find('input, select').val('');
        return;
      }
      $(this).closest('.dependent-row').remove();
    });
  }

  window.initResidenceFamilyForm = function () {
    bindDependentRows($(document));

    $(document).off('click.residenceFamily', '#add-dependent-row').on('click.residenceFamily', '#add-dependent-row', function () {
      var $list = $('#dependents-list');
      var $template = $list.find('.dependent-row').first().clone();
      $template.find('input, select').val('');
      $list.append($template);
      bindDependentRows($template);
    });
  };

  $(function () {
    if (typeof initResidenceFamilyForm === 'function') {
      initResidenceFamilyForm();
    }
  });
})(jQuery);
