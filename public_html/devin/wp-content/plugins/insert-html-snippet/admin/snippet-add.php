<?php 
if ( ! defined( 'ABSPATH' ) ) 
	exit;
global $wpdb;
$goback=1;
$_POST = stripslashes_deep($_POST);
$_POST = xyz_trim_deep($_POST);
$xyz_ihs_insertionLocation ='';
if(isset($_POST) && isset($_POST['addSubmit'])){
	if(!isset($_REQUEST['_wpnonce'])||!wp_verify_nonce($_REQUEST['_wpnonce'],'ihs-add_')){
		wp_nonce_ays( 'ihs-add_' );
		exit;
	} 
	$goback=intval($_POST['goback']);
	$goback++;
	$temp_xyz_ihs_title = str_replace(' ', '', $_POST['snippetTitle']);
	$temp_xyz_ihs_title = str_replace('-', '', $temp_xyz_ihs_title);
	$xyz_ihs_title = str_replace(' ', '-', $_POST['snippetTitle']);
    $xyz_ihs_snippetDescription=sanitize_text_field($_POST['xyz_ihs_snippetDescription']);
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
	$xyz_ihs_content = $_POST['snippetContent'];
	
	if($xyz_ihs_title != "" && $xyz_ihs_content != ""){
		if(ctype_alnum($temp_xyz_ihs_title)){
			$snippet_count = $wpdb->query($wpdb->prepare( 'SELECT * FROM '.$wpdb->prefix.'xyz_ihs_short_code WHERE title=%s' ,$xyz_ihs_title)) ;
			
			if($snippet_count == 0){
				$xyz_shortCode = '[xyz-ihs snippet="'.$xyz_ihs_title.'"]';
				$wpdb->insert($wpdb->prefix.'xyz_ihs_short_code', array('title' =>$xyz_ihs_title,'description'=>$xyz_ihs_snippetDescription,'insertionMethod' => $xyz_ihs_insertionMethod, 'insertionLocation' => $xyz_ihs_insertionLocation, 'insertionLocationType' => $xyz_ihs_insertionLocationType,'content'=>$xyz_ihs_content,'short_code'=>$xyz_shortCode,'status'=>'1'),array('%s','%s','%d','%d','%d','%s','%s','%d'));
				wp_safe_redirect(admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=1'));
			}
			else{
?>
<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
	HTML Snippet already exists. &nbsp;&nbsp;&nbsp;
	<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php	
			}
		}
		else{
?>
<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
	HTML Snippet title can have only alphabets,numbers or hyphen. &nbsp;&nbsp;&nbsp;
	<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php
		}
	}
	else{
?>		
<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
	Fill all mandatory fields. &nbsp;&nbsp;&nbsp;
	<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php 
		}
	
}
?>
<div >
	<fieldset
	style="width: 99%; border: 1px solid #F7F7F7; padding: 10px 0px;">
		<legend>
			<b>
				Add HTML Snippet
			</b>
		</legend>
		<form name="frmmainForm" id="frmmainForm" method="post">
			<?php
wp_nonce_field('ihs-add_'); 
			?>
			<div>
			<input type="hidden"  name="goback" value=<?php echo $goback;?>>
				<table
				style="width: 99%; background-color: #F9F9F9; border: 1px solid #E4E4E4; border-width: 1px;margin: 0 auto">
					<tr>
						<td>
							<br/>
							<div id="shortCode">
							</div>
							<br/>
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
								<option value="<?php echo XYZ_IHS_INSERTION_METHOD['AUTOMATIC']; ?>" <?php if (isset($_POST['xyz_ihs_insertionMethod']) && $_POST['xyz_ihs_insertionMethod'] == XYZ_IHS_INSERTION_METHOD['AUTOMATIC']) {
									   echo "selected";
								   } ?>>Automatic</option>
								<option value="<?php echo XYZ_IHS_INSERTION_METHOD['SHORT_CODE']; ?>" <?php if (isset($_POST['xyz_ihs_insertionMethod']) && $_POST['xyz_ihs_insertionMethod'] == XYZ_IHS_INSERTION_METHOD['SHORT_CODE']) {
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
                        <td style="border-bottom: none;width:1px;">&nbsp;:&nbsp;
                           
                        </td>
                        <td>
                            <div>
    <div class="xyz_ihs_insertionLocationDiv">
    <span id="xyz_ihs_insertion-location-txt">Select Insertion Location </span><span><i class="fa fa-angle-down" aria-hidden="true"></i> <i class="fa fa-angle-up" aria-hidden="true"></i></span>

    </div>
    <input type="hidden" id="xyz_ihs_insertionLocation" name="xyz_ihs_insertionLocation" value="<?php echo intval($xyz_ihs_insertionLocation);?>">
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
					<tr valign="top">
						<td style="border-bottom: none;width:20%;">
							&nbsp;&nbsp;&nbsp;Tracking Name&nbsp;
							<font color="red">
								*
							</font>
						</td>
						<td style="border-bottom: none;width:1px;">
							&nbsp;:&nbsp;
						</td>
						<td>
							<input style="width:80%;"
							type="text" name="snippetTitle" id="snippetTitle"
							value="
<?php if(isset($_POST['snippetTitle'])){ echo esc_attr($_POST['snippetTitle']);}?>">
						</td>
					</tr>
                    <tr valign="top">
                        <td style="border-bottom: none;width:20%;">
                            &nbsp;&nbsp;&nbsp;Description &nbsp;
                        </td>
                        <td style="border-bottom: none;width:1px;">
                        </td>
                        <td>
                        <textarea id="xyz_ihs_snippetDescription"  name="xyz_ihs_snippetDescription" style="resize: both;width:80%;"><?php
                            if (isset($_POST['xyz_ihs_snippetDescription'])) {
                                echo esc_attr(sanitize_text_field($_POST['xyz_ihs_snippetDescription']));
                            } 
                            ?></textarea>
                        </td>
                    </tr>
					<tr>
						<td style="border-bottom: none;width:20%; ">
							&nbsp;&nbsp;&nbsp;HTML code &nbsp;
							<font color="red">
								*
							</font>
						</td>
						<td style="border-bottom: none;width:1px;">
							&nbsp;:&nbsp;
						</td>
						<td >
							<textarea name="snippetContent" style="width:80%;height:150px;"><?php if(isset($_POST['snippetContent'])){ echo esc_textarea($_POST['snippetContent']);}?></textarea>
						</td>
					</tr>
					<tr>
                     <td></td>
					
					
					    <td>
					       
			                
							<input class="button-primary" style="cursor: pointer;"
							type="button" name="back" value="Back" onclick=" window.history.go(-<?php echo $goback;?>);" >
						</td>
						<td>
							<input class="button-primary" style="cursor: pointer;"
							type="submit" name="addSubmit" value="Create" >   
				       </td> 
				         

						 
					</tr>
					<tr>
						<td>
							<br/>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
</div>
<?php
require( dirname( __FILE__ ) . '/snippet-js.php' );
?>
