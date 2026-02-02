<script type="text/javascript">
$ = jQuery;
$(document).ready(() => {
  const XYZ_IHS_INSERTION_LOCATION = <?php echo json_encode(XYZ_IHS_INSERTION_LOCATION); ?>;
  const XYZ_IHS_INSERTION_METHOD = <?php echo json_encode(XYZ_IHS_INSERTION_METHOD); ?>;
  const XYZ_IHS_INSERTION_LOCATION_TYPE = <?php echo json_encode(XYZ_IHS_INSERTION_LOCATION_TYPE); ?>;
  let xyz_ihs_snippetType = $('#xyz_ihs_snippetType').val();
  let xyz_ihs_insertionMethod = $('#xyz_ihs_insertionMethod').val();

  /* General functions starts */
  const toggleListItemVisibility = (locationKey, showHide) => {
    const listItem = $(`#xyz_ihs_uniq_list li[data-value="${XYZ_IHS_INSERTION_LOCATION[locationKey]}"]`);
    listItem.css('display', showHide === 'show' ? 'block' : 'none');
  };

  const getInsertionLocationTextIHS = (xyz_ihs_insertionLocation) => {

   
    XYZ_IHS_INSERTION_LOCATION_txt =
      (xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_HEADER']) ? 'Admin - Run On Header' :
      (xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_FOOTER']) ? 'Admin - Run On Footer' :
      (xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_HEADER']) ? 'Front End - Run On Header' :
      (xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_FOOTER']) ? 'Front End - Run On Footer' :null;
   
      const targetElement = $('#xyz_ihs_insertion-location-txt');
    
    if (targetElement.length > 0 && XYZ_IHS_INSERTION_LOCATION_txt) {
      $('#xyz_ihs_insertion-location-txt').text(XYZ_IHS_INSERTION_LOCATION_txt);
    }
    };
  /* General functions ends */


  if (xyz_ihs_insertionMethod != XYZ_IHS_INSERTION_METHOD['AUTOMATIC']) {
    $('#xyz_ihs_insertionLocationTR').hide();
  } else {
    $('#xyz_ihs_insertionLocationTR').show();
  }

  $('#xyz_ihs_insertionMethod').change((e) => {
    let xyz_ihs_insertionMethod = $(e.target).val();

    if (xyz_ihs_insertionMethod == XYZ_IHS_INSERTION_METHOD['AUTOMATIC']) {
      $('#xyz_ihs_insertionLocationTR').show();

  
    } else {
      $('#xyz_ihs_insertionLocationTR').hide();
  
    }
  });

  let xyz_ihs_insertionLocation = $('#xyz_ihs_insertionLocation').val();
  let XYZ_IHS_INSERTION_LOCATION_txt = 'Select Insertion Location';
  if (xyz_ihs_insertionLocation > 0) {
    getInsertionLocationTextIHS(xyz_ihs_insertionLocation);
  }


  const listItems = document.querySelectorAll('.xyz_ihs_li_option');
  listItems.forEach(item => {
    item.addEventListener('click', () => {

      
      listItems.forEach(li => li.classList.remove('selected'));
      item.classList.add('selected');
      const selectedValue = item.getAttribute('data-value');
      $('#xyz_ihs_insertionLocation').val(selectedValue);
      getInsertionLocationTextIHS(selectedValue);

  
    });
  });
  $('#xyz_ihs_uniq_list').hide();
  $('.xyz_ihs_insertionLocationDiv').on('click', () => {
    $('#xyz_ihs_uniq_list').toggle();
  });
});

</script>
