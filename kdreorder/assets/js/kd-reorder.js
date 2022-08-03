/* KD Re-Order Admin JS */

(function($) {


  $(window).on('load', function() {
    // if the sorting container is present, then we'll init sortable
    if ( $('#kdreorder-items-container').length ) {
      init_kd_reorder();
    }
  });


  // Settings to intialize the sorting (drag-and-drop)
  function init_kd_reorder() {
    $('#kdreorder-items-container').sortable({
      // Options
      cancel: "input.menu-order",
      containment: "#kdreorder-items-container",
      cursor: "move",
      delay: 150,
      distance: 20,
      helper: "clone",
      items: ".thumbnail-item",
      opacity: 0.6,
      placeholder: "sortable-placeholder",
      // Events
      create: function( event, ui ) {
        kd_reorder_drop();
      },
      update: function( event, ui ) {
        kd_reorder_drop();
      }
    });
  }


  // On drop we auto-update the input fields, so that the order can be saved.
  $('#kdreorder-items-container').on( "sortupdate", kd_reorder_drop() );
  function kd_reorder_drop() {
    let number = 1;
    $('#kdreorder-sortable .thumbnail-item').each(function() {
      $(this).find('input.menu-order').val(number);
      number++;
    });
  }

}) (jQuery)
