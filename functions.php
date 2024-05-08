<?php

add_action('init',function(){
	if (isset($_GET['export_entries'])) {
		if (isset($_GET['form_name']) && $_GET['form_name'] == 'ibc') {
			$form_ids   = [5];	
			$email_to = 'lalene@websitego.com';
			$subject = 'AiBUY Entries - AiBUY IBC2023 '. date('F d, Y');
            $message = 'Please find attached the exported entries from AiBUY IBC2023 Exclusive Cocktail Mixer form.';
		}else{
			$form_ids   = [1];
			$email_to = 'lalene@websitego.com';
			$subject = 'AiBUY Entries Export '. date('F d, Y');
            $message = 'Please find attached the exported entries from AiBUY Contact Form.';
		}
		$search = '';
		$fid = '';
		if ( ! $form_ids ) {
			$form_ids = FrmAppHelper::get_param( 'form', '', 'get', 'sanitize_text_field' );
			$search  = FrmAppHelper::get_param( ( isset( $_REQUEST['s'] ) ? 's' : 'search' ), '', 'get', 'sanitize_text_field' );
			$fid     = FrmAppHelper::get_param( 'fid', '', 'get', 'sanitize_text_field' );
		}
		set_time_limit( 0 ); //Remove time limit to execute this function
		$mem_limit = str_replace( 'M', '', ini_get( 'memory_limit' ) );
		if ( (int) $mem_limit < 512 ) {
			wp_raise_memory_limit();
		}
		global $wpdb;
		    foreach ($form_ids as $form_id) {
		$form = FrmForm::getOne( $form_id ); 

		if ( ! $form ) {
			esc_html_e( 'Form not found.', 'formidable' );
			exit(0);
		}
		
		$form_cols = get_fields_for_csv_export( $form_id, $form );
		
		
		$item_id = FrmAppHelper::get_param( 'item_id', 0, 'get', 'sanitize_text_field' );
		if ( ! empty( $item_id ) ) {
			$item_id = explode( ',', $item_id );
		}
		$query = array(
			'form_id' => $form_id,
		);

		if ( $item_id ) {
			$query['id'] = $item_id;
		}

		$query = apply_filters( 'frm_csv_where', $query, compact( 'form_id', 'search', 'fid', 'item_id' ) );

		$entry_ids = FrmDb::get_col( $wpdb->prefix . 'frm_items it', $query );
		unset( $query );
		
		if ( empty( $entry_ids ) ) {
			esc_html_e( 'There are no entries for that form.', 'formidable' );
		} else {
			$mode = 'file';
			$csv_file = FrmCSVExportHelper::generate_csv( compact( 'form', 'entry_ids', 'form_cols', 'mode' ) );
			$attachments = array($csv_file);
      $headers = array(
            'Content-Type: text/html; charset=UTF-8',
      );

      wp_mail($email_to, $subject, $message, $headers, $attachments);
      unlink($csv_file);
      echo '<div class="notice notice-success"><p>Entries exported and sent as email attachment.</p></div>';
		}
			}
		exit(0);
	}
});


function get_fields_for_csv_export( $form_id, $form ) {
	$csv_fields       = FrmField::get_all_for_form( $form_id, '', 'include', 'include' );
	$no_export_fields = FrmField::no_save_fields();
	foreach ( $csv_fields as $k => $f ) {
		if ( in_array( $f->type, $no_export_fields, true ) ) {
			unset( $csv_fields[ $k ] );
		}
	}

	return apply_filters( 'frm_fields_for_csv_export', $csv_fields, compact( 'form' ) );
}
