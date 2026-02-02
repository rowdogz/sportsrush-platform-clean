<?php 
if ( ! defined( 'ABSPATH' ) ) 
	exit;

global $wpdb;
$goback=1;
$xyz_ihs_snippetId = intval($_GET['snippetId']);

$xyz_ihs_message = '';
if(isset($_GET['xyz_ihs_msg'])){
	$xyz_ihs_message = intval($_GET['xyz_ihs_msg']);
}
if($xyz_ihs_message == 1){

	?>
<!-- <div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
HTML Snippet successfully updated.&nbsp;&nbsp;&nbsp;<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div> -->
			<div class="notice notice-success is-dismissible">
                <p><strong>HTML Snippet successfully updated. &nbsp;&nbsp;&nbsp;</strong></p>
</div>
<?php
}

if(isset($_GET['goback']))
    $goback= intval($_GET['goback']);

    
$xyz_ihs_snippetId = intval($_GET['snippetId']);
if(isset($_POST) && isset($_POST['updateSubmit'])){
	if (!isset($_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ihs-edit_'.$xyz_ihs_snippetId )) {
	wp_nonce_ays( 'ihs-edit_'.$xyz_ihs_snippetId );
	exit;
	} 
	$goback=intval($_POST['goback']);
	$goback++;

	$_POST = stripslashes_deep($_POST);
	$_POST = xyz_trim_deep($_POST);
	
	
	
	$temp_xyz_ihs_title = str_replace(' ', '', $_POST['snippetTitle']);
	$temp_xyz_ihs_title = str_replace('-', '', $temp_xyz_ihs_title);
	
	$xyz_ihs_title = str_replace(' ', '-', $_POST['snippetTitle']);
	$xyz_ihs_snippetDescription=sanitize_text_field($_POST['xyz_ihs_snippetDescription']);
	$xyz_ihs_content = $_POST['snippetContent'];
    $xyz_ihs_insertionMethod = intval($_POST['xyz_ihs_insertionMethod']);
    $xyz_ihs_insertionLocation = intval($_POST['xyz_ihs_insertionLocation']);

if ($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_HEADER'] || $xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_FOOTER']) {
    $xyz_ihs_insertionLocationType = XYZ_IHS_INSERTION_LOCATION_TYPE['ADMIN'];
}
else if($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_HEADER'] || $xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_FOOTER']){
    $xyz_ihs_insertionLocationType = XYZ_IHS_INSERTION_LOCATION_TYPE['FRONT_END']; 
}
else{
    $xyz_ihs_insertionLocationType = 0; 
}

	if($xyz_ihs_title != "" && $xyz_ihs_content != ""){
		
		if(ctype_alnum($temp_xyz_ihs_title))
		{
		$snippet_count = $wpdb->query($wpdb->prepare( 'SELECT * FROM '.$wpdb->prefix.'xyz_ihs_short_code WHERE id!=%d AND title=%s LIMIT 0,1',$xyz_ihs_snippetId,$xyz_ihs_title)) ;
		
		if($snippet_count == 0){
			$xyz_shortCode = '[xyz-ihs snippet="'.$xyz_ihs_title.'"]';
			
			$wpdb->update($wpdb->prefix.'xyz_ihs_short_code', array('title'=>$xyz_ihs_title,'description'=>$xyz_ihs_snippetDescription,'insertionMethod' => $xyz_ihs_insertionMethod, 'insertionLocation' => $xyz_ihs_insertionLocation, 'insertionLocationType' => $xyz_ihs_insertionLocationType,'content'=>$xyz_ihs_content,'short_code'=>$xyz_shortCode,), array('id'=>$xyz_ihs_snippetId));
			
			wp_safe_redirect(admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-edit&snippetId='.$xyz_ihs_snippetId.'&xyz_ihs_msg=1'.'&goback='.$goback));
	
		}
		else{
			?>
			<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
			HTML Snippet already exists. &nbsp;&nbsp;&nbsp;<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
			</div>
			<?php	
	
		}
		}
		else
		{
			?>
		<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
		HTML Snippet title can have only alphabets,numbers or hyphen. &nbsp;&nbsp;&nbsp;<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
		</div>
		<?php
		
		}
		
	
	}else{
?>		
		<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
			Fill all mandatory fields. &nbsp;&nbsp;&nbsp;<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
		</div>
<?php 
		}

	
}

global $wpdb;


$snippetDetails = $wpdb->get_results($wpdb->prepare( 'SELECT * FROM '.$wpdb->prefix.'xyz_ihs_short_code WHERE id=%d LIMIT 0,1',$xyz_ihs_snippetId )) ;
$snippetDetails = $snippetDetails[0];

$xyz_ihs_insertionMethod = $snippetDetails->insertionMethod;
$xyz_ihs_insertionLocation = $snippetDetails->insertionLocation;
?>

<div >
	<fieldset
		style="width: 99%; border: 1px solid #F7F7F7; padding: 10px 0px;">
		<legend>
			<b>Edit HTML Snippet</b>
		</legend>
		<form name="frmmainForm" id="frmmainForm" method="post">
			<?php wp_nonce_field( 'ihs-edit_'.$xyz_ihs_snippetId ); ?>
			
			<input type="hidden" id="snippetId" name="snippetId"
				value="<?php if(isset($_POST['snippetId'])){ echo esc_attr($_POST['snippetId']);}else{ echo esc_attr($snippetDetails->id); }?>">
			<div>
			<input type="hidden"  name="goback" value= <?php echo $goback;?>>
				<table
					style="width: 99%; background-color: #F9F9F9; border: 1px solid #E4E4E4; border-width: 1px;margin: 0 auto">
					<tr><td><br/>
					<div id="shortCode"></div>
					<br/></td></tr>
					<tr valign="top">
						<td style="border-bottom: none;width:20%;">&nbsp;&nbsp;&nbsp;Tracking Name&nbsp;<font color="red">*</font></td>
						<td style="border-bottom: none;width:1px;">&nbsp;:&nbsp;</td>
						<td><input style="width:80%;"
							type="text" name="snippetTitle" id="snippetTitle"
							value="<?php if(isset($_POST['snippetTitle'])){ echo esc_attr($_POST['snippetTitle']);}else{ echo esc_attr($snippetDetails->title); }?>"></td>
					</tr>
					<tr valign="top">
						<td style="border-bottom: none;width:20%;">&nbsp;&nbsp;&nbsp;Description &nbsp;</td>
						<td style="border-bottom: none;width:1px;">&nbsp;:&nbsp;</td>
						<td>
							<textarea id="xyz_ihs_snippetDescription"  name="xyz_ihs_snippetDescription" style="resize: both;width:80%;"><?php
                           if(isset($_POST['xyz_ihs_snippetDescription'])){ echo esc_attr($_POST['xyz_ihs_snippetDescription']);}else{ echo esc_attr($snippetDetails->description); } ?></textarea>
						</td>
					</tr>
                    <tr valign="top">
                        <td style="border-bottom: none;width:20%;">
                            &nbsp;&nbsp;&nbsp;Placement Methods &nbsp;
                            <font color="red">
                                *
                            </font>
                        </td>
                        <td style="border-bottom: none;width:1px;">
						&nbsp;:&nbsp;
                        </td>
                        <td>
                            <select class="xyz_ihs_uniq_select" name="xyz_ihs_insertionMethod"
                                id="xyz_ihs_insertionMethod">
                                <option value="<?php echo XYZ_IHS_INSERTION_METHOD['AUTOMATIC']; ?>" <?php if ($xyz_ihs_insertionMethod == XYZ_IHS_INSERTION_METHOD['AUTOMATIC']) {
                                       echo "selected";
                                   } ?>>Automatic</option>
                                <option value="<?php echo XYZ_IHS_INSERTION_METHOD['SHORT_CODE']; ?>" <?php if ($xyz_ihs_insertionMethod == XYZ_IHS_INSERTION_METHOD['SHORT_CODE']) {
                                       echo "selected";
                                   } ?>>Short code (Manual)</option>
                         
                            </select>
                        </td>
                    </tr>


                    <tr valign="top" id="xyz_ihs_insertionLocationTR">
                        <td style="border-bottom: none;width:20%;">
                            &nbsp;&nbsp;&nbsp;Insertion Location &nbsp;
                            <font color="red">
                                *
                            </font>
                        </td>
                        <td style="border-bottom: none;width:1px;">
						&nbsp;:&nbsp;
                        </td>
                        <td>
                            <div>
    <div class="xyz_ihs_insertionLocationDiv">
    <span id="xyz_ihs_insertion-location-txt">Select Insertion Location </span><span><i class="fa fa-angle-down" aria-hidden="true"></i> <i class="fa fa-angle-up" aria-hidden="true"></i></span>

    </div>
    <input type="hidden" id="xyz_ihs_insertionLocation" name="xyz_ihs_insertionLocation" value="<?php echo $xyz_ihs_insertionLocation;?>">
    <ul id="xyz_ihs_uniq_list" class="xyz_ihs_uniq_list">
    <div class="xyz_ihs_left-column">
    
    <li class="xyz_ihs_li_h2"><label>Admin</label></li>
    <li class="xyz_ihs_li_option <?php echo ($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_HEADER']) ? 'selected' : ''; ?>" 
        data-value="<?php echo XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_HEADER']; ?>">
        Run on header
    </li>
    <li class="xyz_ihs_li_option <?php echo ($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_FOOTER']) ? 'selected' : ''; ?>" 
        data-value="<?php echo XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_FOOTER']; ?>">
        Run on footer
    </li>
    
    <li class="xyz_ihs_li_h2"><label>Front end</label></li>

    <li class="xyz_ihs_li_option <?php echo ($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_HEADER']) ? 'selected' : ''; ?>" 
        data-value="<?php echo XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_HEADER']; ?>">
        Run on header
    </li>
    <li class="xyz_ihs_li_option <?php echo ($xyz_ihs_insertionLocation == XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_FOOTER']) ? 'selected' : ''; ?>" 
        data-value="<?php echo XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_FOOTER']; ?>">
        Run on footer
    </li>
   
    </div>
    <div class="xyz_ihs_right-column">

    </div>
</ul>
</div>

                        </td>
                    </tr>
					<tr>
						<td style="border-bottom: none;width:20%; ">&nbsp;&nbsp;&nbsp;HTML code &nbsp;<font color="red">*</font></td>
						<td style="border-bottom: none;width:1px;">&nbsp;:&nbsp;</td>
						<td >
							<textarea name="snippetContent" style="width:80%;height:150px;"><?php if(isset($_POST['snippetContent'])){ echo esc_textarea($_POST['snippetContent']);}else{ echo esc_textarea($snippetDetails->content); }?></textarea>
						</td>
					</tr>				

				<tr>
				  <td></td>
                    		    <td>
					       
			                
							<input class="button-primary" style="cursor: pointer;"
							type="button" name="back"   onclick=" window.history.go(-<?php echo $goback;?>);" value="Back" >
						</td>
					<td><input class="button-primary" style="cursor: pointer;" type="submit" name="updateSubmit" value="Update"></td>
					
				</tr>
				<tr><td><br/></td></tr>
				</table>
			</div>

		</form>
	</fieldset>

</div>
<?php
require( dirname( __FILE__ ) . '/snippet-js.php' );
?>
