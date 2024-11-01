<?php

// factorize out PDF document JS control function
if (is_admin()) {


  add_action('admin_enqueue_scripts', function() {
      wp_enqueue_script('winddoc_genera_fattura', plugins_url("WD_js.js", __FILE__), array('jquery'));
  });

  add_action('wp_ajax_invoice_admin_command', function() {
    
    $_POST_arg_type = sanitize_text_field($_POST["args"]["type"]);
    $_POST_arg_id= sanitize_text_field($_POST["args"]["id"]);

    if($_POST_arg_type=="fatture"){
      
      check_ajax_referer("winddoc_genera_documento".$_POST_arg_id."fatture", 'security');

      $WindDoc_Helper= new WindDoc_Helper();
      $ret = $WindDoc_Helper->creaFattura($_POST_arg_id);
      wp_send_json($ret);
    }
    if($_POST_arg_type=="ordini"){

      check_ajax_referer("winddoc_genera_documento".$_POST_arg_id."ordini", 'security');

      $WindDoc_Helper= new WindDoc_Helper();
      $ret = $WindDoc_Helper->creaOrdine($_POST_arg_id);
      wp_send_json($ret);
    }
  });


  add_action('admin_head', 'winddoc_custom_css');

  function winddoc_custom_css() {
    echo '<style>
    .loading-overlay {
      display: table;
      opacity: 0.7;
    }

    .loading-overlay-content {
      text-transform: uppercase;
      letter-spacing: 0.4em;
      font-size: 1.15em;
      font-weight: bold;
      text-align: center;
      display: table-cell;
      vertical-align: middle;
    }

    .loading-overlay.loading-theme-light {
      background-color: #fff;
      color: #000;
    }

    .loading-overlay.loading-theme-dark {
      background-color: #000;
      color: #fff;
    }
        </style>';
  }
}
add_action('woocommerce_my_account_my_orders_column_order-actions', function($order) {
  $actions = wc_get_account_orders_actions( $order );

	if ( ! empty( $actions ) ) {
		foreach ( $actions as $key => $action ) {
			echo ('<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . '">' . $action['name'] . '</a>');
		}
	}

  $WindDoc_Helper= new WindDoc_Helper();
  $dt = $WindDoc_Helper->dettaglioOrdine($order->get_id());

  if(get_option('WD_WINDDOC_ORDINI_ENABLE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_ordine_winddoc) && $dt[0]->url_ordine_winddoc!=""){
      echo ("<a target='_blank' style='text-decoration:none;' class='woocommerce-button button' href='".$dt[0]->url_ordine_winddoc."'>Visualizza Ordine</a>");
    }
  }

  if(get_option('WD_WINDDOC_FATTURE_ENABLE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
      echo ("<a target='_blank' style='text-decoration:none;' class='woocommerce-button button'  href='".$dt[0]->url_invoice_winddoc."'>Visualizza Fattua</a>");
    }
  }
  if(get_option('WD_WINDDOC_FATTURE_ENABLE')==0 && get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
      echo ("<a target='_blank' style='text-decoration:none;' class='woocommerce-button button'  href='".$dt[0]->url_invoice_winddoc."'>Visualizza Ricevuta</a>");
    }
  }

});



add_filter( 'woocommerce_admin_billing_fields',  function($billing_fields) {

      global $pagenow;
      $post_type = "";
      $GET_post = sanitize_text_field($_GET['post']);
      $GET_post_type = sanitize_text_field($_GET['post_type']);

      if($GET_post!=""){
        $post_type = get_post_type($GET_post);
      }
      if($GET_post_type!=""){
        $post_type = $GET_post_type;
      }
      if( $post_type === 'shop_order' ){

        if(get_option('WD_WINDDOC_CODICE_FISCALE')!=0){
          $billing_fields['billing_codice_fiscale'] = array(
              'type'       => 'text',
              'placeholder' => 'Codice fiscale',
              'label'      => 'Codice Fiscale', //
              'required'   => (get_option('WD_WINDDOC_CODICE_FISCALE')==2 ? true : false),
              'priority'   => 120,
              'clear'      => true
           );
         }

        if(get_option('WD_WINDDOC_PARTITA_IVA')!=0){
          $billing_fields['billing_partita_iva'] = array(
            'type'       => 'text',
            'placeholder' => 'Partita IVA',
            'label'      => 'Partita IVA / VAT', //
            'required'   => (get_option('WD_WINDDOC_PARTITA_IVA')==2 ? true : false),
            'priority'   => 120,
            'clear'      => true,
          );
        }

        if(get_option('WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO')!=0){
            $billing_fields['billing_codice_destinatario'] = array(
                'type' => 'text',
                'placeholder' => 'Codice destinatario',
                'label' => 'Codice destinatario',
                'priority' => 120,
                'clear' => true
            );

            $billing_fields['billing_pec'] = array(
                'type' => 'email',
                'placeholder' => 'E-mail PEC',
                'label' => 'E-mail PEC',
                'validate' => array('email'),
                'priority' => 120,
                'clear' => true
            );
          }

      }
      return $billing_fields;
}, 10, 1 );


add_action('woocommerce_checkout_fields', function($fields) {

  if(get_option('WD_WINDDOC_CODICE_FISCALE')!=0){
    $fields['billing']['billing_codice_fiscale'] = array(
        'type'       => 'text',
        'placeholder' => 'Codice fiscale',
        'label'      => 'Codice Fiscale', // edited label Davide Iandoli 24.01.2019
        'required'   => (get_option('WD_WINDDOC_CODICE_FISCALE')==2 ? true : false),
        'priority'   => 120,
        'clear'      => true
     );
   }

  if(get_option('WD_WINDDOC_PARTITA_IVA')!=0){
    $fields['billing']['billing_partita_iva'] = array(
      'type'       => 'text',
      'placeholder' => 'Partita IVA',
      'label'      => 'Partita IVA / VA', // edited label Davide Iandoli 24.01.2019
      'required'   => (get_option('WD_WINDDOC_PARTITA_IVA')==2 ? true : false),
      'priority'   => 120,
      'clear'      => true,
    );
  }

  if(get_option('WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO')!=0){
      $fields['billing']['billing_codice_destinatario'] = array(
          'type' => 'text',
          'placeholder' => 'Codice destinatario',
          'label' => 'Codice destinatario',
          'priority' => 120,
          'clear' => true
      );

      $fields['billing']['billing_pec'] = array(
          'type' => 'email',
          'placeholder' => 'E-mail PEC',
          'label' => 'E-mail PEC',
          'validate' => array('email'),
          'priority' => 120,
          'clear' => true
      );
  }

  return $fields;

});


add_action( 'woocommerce_order_status_changed', function($order_id){
  //if (is_admin()) {
    $order = new WC_Order( $order_id );
    
    if(get_option('WD_WINDDOC_FATTURE_ENABLE')==1 || get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==1){
      if(get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==1 || get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==1){
        
        if($order->status=="processing" || $order->status=="completed"){
          $payment_method = $order->get_payment_method();
          if($payment_method=="cod" || $payment_method=="ppec_paypal"){
            $WindDoc_Helper= new WindDoc_Helper();
            $WindDoc_Helper->creaFattura($order_id);
          }
        }					                
      }
    }

    if(get_option('WD_WINDDOC_ORDINI_ENABLE')==1){
      if(get_option('WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI')==1){
        if($order->status=="processing" || $order->status=="completed"){
          $WindDoc_Helper= new WindDoc_Helper();
          $WindDoc_Helper->creaOrdine($order_id,true);
        }
      }
    }


    if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){                            
      $id_calendario_attivitacal = array();
      foreach ($order->get_items('line_item') as $item){
        $data = $item->get_meta_data();        
        foreach($data as $dd){
            if($dd->key=="id_calendario_attivitacal"){                                                                                
              $id_calendario_attivitacal[] = $dd->value;
            }
        }
      }
      
      if( get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==0 && 
          get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==0 && 
          get_option('WD_WINDDOC_ORDINI_ENABLE')==0){
            //Se non ho attivato le fatture ma solo gli appuntamenti lo imposto come prenotato
            if($order->status=="processing" || $order->status=="completed"){
              
              if(count($id_calendario_attivitacal)>0){
                foreach($id_calendario_attivitacal as $id_cal){
                  $WindDoc_Helper= new WindDoc_Helper();
                  $WindDoc_Helper->prenotaAppuntamento($id_cal);                
                }
              }
            }
      }
      if($order->status=="pending" || $order->status=="on-hold" || $order->status=="checkout-draft"){
       
        //Metto appuntamento come opzionato                   
        if(count($id_calendario_attivitacal)>0){
          foreach($id_calendario_attivitacal as $id_cal){
            $WindDoc_Helper= new WindDoc_Helper();
            $WindDoc_Helper->setAppuntamentoOpzionato($id_cal);  
           
          }
        }
      }elseif($order->status=="cancelled" || $order->status=="refunded" || $order->status=="failed"){
        //Cancello Opzione
        if(count($id_calendario_attivitacal)>0){
          foreach($id_calendario_attivitacal as $id_cal){
            $WindDoc_Helper= new WindDoc_Helper();
            $WindDoc_Helper->ripristinaAppuntamento($id_cal);                
          }
        }
      }   
    }


//  }
});

/*
add_action( 'woocommerce_after_order_object_save', function($order){
 
});
*/

//Appena creato ordini metto l'appuntamento come prenotato... o come confermato se ha subito pagato

add_action( 'woocommerce_thankyou', function($order_id){

  if (!is_admin()) {
    if(get_option('WD_WINDDOC_ORDINI_ENABLE')==1){
      if(get_option('WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI')==1){
        $WindDoc_Helper= new WindDoc_Helper();
        $WindDoc_Helper->creaOrdine($order_id,true);
      }
    }

    if(get_option('WD_WINDDOC_FATTURE_ENABLE')==1 || get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==1){
      if(get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==1){
        $order = new WC_Order( $order_id );
        $payment_method = $order->get_payment_method();
        if($payment_method=="cod" || $payment_method=="ppec_paypal"){
          $WindDoc_Helper= new WindDoc_Helper();
          $WindDoc_Helper->creaFattura($order_id,true);
        }
      }
    }
  }else{
    if(get_option('WD_WINDDOC_FATTURE_ENABLE')==1 || get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==1){
      if(get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==1){
        $order = new WC_Order( $order_id );
        $payment_method = $order->get_payment_method();
        if($payment_method=="cod" || $payment_method=="ppec_paypal"){
          $WindDoc_Helper= new WindDoc_Helper();
          $WindDoc_Helper->creaFattura($order_id);
        }
      }
    }
  }



},  10, 1  );

add_action('woocommerce_admin_order_data_after_order_details', function($order) {
  $WindDoc_Helper= new WindDoc_Helper();
  $dt = $WindDoc_Helper->dettaglioOrdine($order->get_id());
  echo '<p class="form-field form-field-wide"><br><h2>WindDoc</h2><br>';
  if(get_option('WD_WINDDOC_ORDINI_ENABLE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_ordine_winddoc) && $dt[0]->url_ordine_winddoc!=""){
      echo "<a target='_blank' style='text-decoration:none;' class='order-status status-processing' href='".$dt[0]->url_ordine_winddoc."'>&nbsp;&nbsp;Visualizza Ordine&nbsp;&nbsp;</a>&nbsp;&nbsp;";
    }else{
      echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"ordini\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."ordini")."\")'>&nbsp;&nbsp;Genera Ordine&nbsp;&nbsp;</a>&nbsp;&nbsp;";
    }
  }

  if(get_option('WD_WINDDOC_FATTURE_ENABLE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
      echo "<a target='_blank' style='text-decoration:none;' class='order-status status-processing'  href='".$dt[0]->url_invoice_winddoc."'>&nbsp;&nbsp;Visualizza Fattua&nbsp;&nbsp;</a>";
    }else{
      echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"fatture\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."fatture")."\")'>&nbsp;&nbsp;Genera Fattura&nbsp;&nbsp;</a>";
    }
  }
  if(get_option('WD_WINDDOC_FATTURE_ENABLE')==0 && get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')!=0){
    if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
      echo "<a target='_blank' style='text-decoration:none;' class='order-status status-processing'  href='".$dt[0]->url_invoice_winddoc."'>&nbsp;&nbsp;Visualizza Ricevuta&nbsp;&nbsp;</a>";
    }else{
      echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"fatture\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."fatture")."\")'>&nbsp;&nbsp;Genera Ricevuta&nbsp;&nbsp;</a>";
    }
  }
  echo '</p>';
}, 10, 1);

add_filter('manage_edit-shop_order_columns', function($columns) {
    $col = is_array($columns) ? $columns : array();

    if(get_option('WD_WINDDOC_ORDINI_ENABLE')!=0){
      $col['winddoc-order'] = 'WindDoc Ordini';
    }
    if(get_option('WD_WINDDOC_FATTURE_ENABLE')!=0){
      $col['winddoc-invoice'] = 'WindDoc Fatture';
    }
    if(get_option('WD_WINDDOC_FATTURE_ENABLE')==0 && get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')!=0){
      $col['winddoc-invoice'] = 'WindDoc Ricevute';
    }
    return $col;
});


add_action('manage_shop_order_posts_custom_column', function($column) {

    if ($column == 'winddoc-order') {
        global $post;
        $order = new \WC_Order($post->ID);
        $WindDoc_Helper= new WindDoc_Helper();
        $dt = $WindDoc_Helper->dettaglioOrdine($order->get_id());
        if(isset($dt[0]) && isset($dt[0]->url_ordine_winddoc) && $dt[0]->url_ordine_winddoc!=""){
          echo "<a target='_blank' class='order-status status-processing' href='".$dt[0]->url_ordine_winddoc."'>&nbsp;&nbsp;Visualizza Ordine&nbsp;&nbsp;</a>";
        }else{
          echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"ordini\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."ordini")."\")'>&nbsp;&nbsp;Genera Ordine&nbsp;&nbsp;</a>";
        }
    }
    if ($column == 'winddoc-invoice') {
        global $post;
        $order = new \WC_Order($post->ID);
        $WindDoc_Helper= new WindDoc_Helper();
        $dt = $WindDoc_Helper->dettaglioOrdine($order->get_id());
        if(get_option('WD_WINDDOC_FATTURE_ENABLE')!=0){
          if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
            echo "<a target='_blank' class='order-status status-processing'  href='".$dt[0]->url_invoice_winddoc."'>&nbsp;&nbsp;Visualizza Fattua&nbsp;&nbsp;</a>";
          }else{
            echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"fatture\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."fatture")."\")'>&nbsp;&nbsp;Genera Fattura&nbsp;&nbsp;</a>";
          } 
        }

        if(get_option('WD_WINDDOC_FATTURE_ENABLE')==0 && get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')!=0){
          if(isset($dt[0]) && isset($dt[0]->url_invoice_winddoc) && $dt[0]->url_invoice_winddoc!=""){
            echo "<a target='_blank' class='order-status status-processing'  href='".$dt[0]->url_invoice_winddoc."'>&nbsp;&nbsp;Visualizza Ricevuta&nbsp;&nbsp;</a>";
          }else{
            echo "<a class='order-status status-on-hold' onclick='winddoc_genera_documento(\"".$order->get_id()."\",\"fatture\",\"".wp_create_nonce("winddoc_genera_documento".$order->get_id()."fatture")."\")'>&nbsp;&nbsp;Genera Ricevuta&nbsp;&nbsp;</a>";
          } 
        }
        
    }

}, 2);



//Aggiungere il TAB per selezionare l'appuntamento
add_filter( 'woocommerce_product_data_tabs', 'wdn_woo_appuntamenti_winddoc_tab', 98 );
function wdn_woo_appuntamenti_winddoc_tab( $tabs ) {
    
	if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){  
    
      $tabs['appuntamenti_winddoc'] = array(
      'label'		=> __( 'WindDoc Appuntamenti', 'woocommerce' ),
      'target'	=> 'appuntamenti_winddoc',
      'class'		=> array( 'show_if_simple', 'show_if_variable'  ),
    );
  }
	return $tabs;
}

add_action( 'woocommerce_product_data_panels', 'wdn_add_appuntamenti_winddoc_fields' );
function wdn_add_appuntamenti_winddoc_fields() {
	  global $woocommerce, $post,$product_object;

    $WindDoc_Helper = new WindDoc_Helper();
    $calendari_attivita = $WindDoc_Helper->getCalendariAttivita();
    /*$corsi_lezioni = $WDN_Helper->listaCorsiLezione();    
    $corsi_pacchetti = $WDN_Helper->listaCorsiPacchetti();
    $prodotti = $WDN_Helper->listaProdotti();
    $flotta = $WDN_Helper->listaFlotta();*/
    
    
    $_wd = maybe_unserialize(get_post_meta($product_object->id, '_wd', true));
    $_wd_winddoc_calendar_label = maybe_unserialize(get_post_meta($product_object->id, '_wd_winddoc_calendar_label', true));
    
    ?>
   
   <div id="appuntamenti_winddoc" class="panel wc-metaboxes-wrapper hidden" style="display: none;">
    
   <p class="form-field winddoc_calendar_label ">
		<label for="winddoc_calendar_label">Testo visualizzato</label>
    <input type="text" class="short" style="" name="_wd_winddoc_calendar_label" id="winddoc_calendar_label" value="<?php echo $_wd_winddoc_calendar_label; ?>" placeholder="es: Seleziona lo Studio"> 
  </p>

<button type="button" class="button button-primary" onclick="add_wd_calendario()">Aggiungi Calendario</button>
    </div>
    
    <script type="text/javascript">
        var wnd_num = 0;

        function add_wd_calendario(id){
            var html = '';
            html = html + '<div id="WD_'+wnd_num+'">';
            html = html + '<p class=" form-field"><label for="_id_flotta'+wnd_num+'">Seleziona il calendario di attività di prenotazione</label>';
            
            html = html + '<select id="_id_flotta'+wnd_num+'" name="_wd['+wnd_num+'][id_calendario_attivita]" class="select short">';
            <?php foreach ($calendari_attivita as $key => $value) :?>
                html = html + '<option value="<?php echo esc_js($key);?>"';
                if(id=='<?php echo esc_js($key);?>'){
                    html = html + ' selected="selected"';
                }
                html = html + '><?php echo esc_js($value);?></option>';
            <?php endforeach;?>
            html = html + '</select>';            
            html = html + '<button type="button" onclick="jQuery(\'#WD_'+wnd_num+'\').remove();"><i class="wp-menu-image dashicons-before dashicons-trash"></i></button>';
            html = html + '</p>';
            html = html + '</div>';
            wnd_num++;
            jQuery("#appuntamenti_winddoc").append(html);
        }

        <?php if(is_array($_wd)) foreach($_wd as $v) :?>
              add_wd_calendario('<?php echo esc_js($v["id_calendario_attivita"]);?>');
        <?php endforeach;?>

    </script>
    <?php
}


function recursive_sanitize_text_field($array) {
  foreach ( $array as $key => &$value ) {
      if ( is_array( $value ) ) {
          $value = recursive_sanitize_text_field($value);
      }
      else {
          $value = sanitize_text_field( $value );
      }
  }

  return $array;
}

/**
 * Save the custom fields.
 */
function wdn_save_appuntamenti_winddoc_fields( $post_id ) {
	
  $_wd = recursive_sanitize_text_field($_POST['_wd']);
  update_post_meta( $post_id, '_wd', $_wd );

  $_wd_winddoc_calendar_label = recursive_sanitize_text_field($_POST['_wd_winddoc_calendar_label']);    
  update_post_meta( $post_id, '_wd_winddoc_calendar_label', $_wd_winddoc_calendar_label );
  
	
}
add_action( 'woocommerce_process_product_meta_simple', 'wdn_save_appuntamenti_winddoc_fields'  );
add_action( 'woocommerce_process_product_meta_variable', 'wdn_save_appuntamenti_winddoc_fields'  );


//disabilita la funzione ajax di aggiunta al carrello
function wd_woocommerce_appuntamenti_product_add_to_cart_url( $url, $product ) {
  $_wds = maybe_unserialize(get_post_meta($product->id, '_wd', true));
  if(is_array($_wds)){
    foreach($_wds as $_wd){
      if(isset($_wd["id_calendario_attivita"]) &&  $_wd["id_calendario_attivita"]!=""){
        return $product->get_permalink();
      }
    }
  }
  return $url;
}
add_action( 'woocommerce_product_add_to_cart_url', 'wd_woocommerce_appuntamenti_product_add_to_cart_url' ,10,2 );

//disabilita la funzione ajax di aggiunta al carrello
function wd_woocommerce_appuntamenti_product_add_to_cart_text($text, $product) {
  $_wds = maybe_unserialize(get_post_meta($product->id, '_wd', true));
  if(is_array($_wds)){
    foreach($_wds as $_wd){
      if(isset($_wd["id_calendario_attivita"]) &&  $_wd["id_calendario_attivita"]!=""){
        return __( 'Read more', 'woocommerce' );
      }
    }
  }
  return $text;
}
add_action( 'woocommerce_product_add_to_cart_text', 'wd_woocommerce_appuntamenti_product_add_to_cart_text' ,10,2 );

//disabilita la funzione ajax di aggiunta al carrello
function wd_woocommerce_appuntamenti_product_supports($feature, $class,$product) {
  
  $_wds = maybe_unserialize(get_post_meta($product->id, '_wd', true));  
  if(is_array($_wds)){
    foreach($_wds as $_wd){    
      if(isset($_wd["id_calendario_attivita"]) &&  $_wd["id_calendario_attivita"]!=""){
        return false;
      }
    }
  }
  
  return $feature;
}
add_action( 'woocommerce_product_supports', 'wd_woocommerce_appuntamenti_product_supports' ,10,3 );



//Inserisco il calendario
add_action('woocommerce_before_add_to_cart_form', 'wd_woocommerce_appuntamenti_before_add_to_cart_form' );
function wd_woocommerce_appuntamenti_before_add_to_cart_form(){
    Global $product;
    $_wds = maybe_unserialize(get_post_meta($product->id, '_wd', true));
    $_wd_winddoc_calendar_label = maybe_unserialize(get_post_meta($product->id, '_wd_winddoc_calendar_label', true));
    $id_calendario_attivita = "";
    if(is_array($_wds)){
      foreach($_wds as $_wd){
        if(isset($_wd["id_calendario_attivita"]) &&  $_wd["id_calendario_attivita"]!=""){
          $id_calendario_attivita = $_wd["id_calendario_attivita"];
        }
        if($id_calendario_attivita!=""){
            $calendari = array();
          
            foreach($_wds as $att){
              $WindDoc_Helper= new WindDoc_Helper();            
              
              $calendari[] = $WindDoc_Helper->getDettAttivita($att["id_calendario_attivita"]);
            }
            
            include(WC_WINDDOC_PLUGIN_PATH."template/calendario_prodotto.php");
            break;     
        }
      }
    }
}




add_action("wp_ajax_frontend_action_winddoc_appuntamenti_get_book_month" , "frontend_action_winddoc_appuntamenti_get_book_month");
add_action("wp_ajax_nopriv_frontend_action_winddoc_appuntamenti_get_book_month" , "frontend_action_winddoc_appuntamenti_get_book_month");
function frontend_action_winddoc_appuntamenti_get_book_month(){
    
    $to = sanitize_text_field($_POST['to']);    
    $id_calendario_attivita = sanitize_text_field($_POST["id_calendario_attivita"]);
    $ret = array();

    $data = sanitize_text_field($_POST["data"]);
    $d = explode("-",$data);
    $from = $d[0]."-".$d[1]."-01";
    if($d[1]==12){
      $to = ($d[0]+1)."-01-01";
    }else{
      $to = $d[0]."-".($d[1]+1)."-01";
    }

    $WindDoc_Helper = new WindDoc_Helper();
    $ret = $WindDoc_Helper->getBookingFromTo($id_calendario_attivita,$from,$to);
    $return = array();
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    foreach($ret as $r){  
      $find = false;
      foreach($items as $item){                  
        if($r["id_calendario_attivitacal"] == $item["id_calendario_attivitacal"]){
          $find = true;
        }
      }
      if(!$find){
        $return[] = $r;
      }
    }
    //acl($ret);
    /*
    $mysqlTimestampfrom = strtotime($from." 23:59:59");
    $mysqlTimestampto = strtotime($to." 23:59:59");
    if($mysqlTimestampfrom<time()){
        $from = date ('Y-m-d');
    }
    
    if($mysqlTimestampto>time()){
        $WDN_Helper = new WDN_Helper();
        //$ret = $WDN_Helper->getBookingFromTo($from." 00:00:00",$to." 23:59:59",$id_flotta);
    }*/
    echo json_encode($return);
    wp_die();
}


// Creo un campo dove inseriso le info del booking
add_action( 'woocommerce_before_add_to_cart_button', 'hidden_field_before_add_to_cart_button', 5 );
function hidden_field_before_add_to_cart_button(){
    echo '<input type="hidden" name="id_calendario_attivitacal" id="hidden_id_calendario_attivitacal" value="">
          <input type="hidden" name="id_calendario_attivitacal_data" id="hidden_id_calendario_attivitacal_data" value="">
          ';
}
// Salvo il campo nel carrello
add_filter( 'woocommerce_add_cart_item_data', 'add_id_calendario_attivitacal_to_cart_item_data', 20, 2 );
function add_id_calendario_attivitacal_to_cart_item_data( $cart_item_data, $product_id ){
    if( isset($_POST['id_calendario_attivitacal']) && ! empty($_POST['id_calendario_attivitacal']) ){
        $id_calendario_attivitacal = sanitize_textarea_field( $_POST['id_calendario_attivitacal'] );
        $cart_item_data['id_calendario_attivitacal'] = $id_calendario_attivitacal;

        $id_calendario_attivitacal_data = sanitize_textarea_field( $_POST['id_calendario_attivitacal_data'] );
        $cart_item_data['id_calendario_attivitacal_data'] = $id_calendario_attivitacal_data;
    }
    return $cart_item_data;
}


// aggiungi i campi nell'ordine
add_action( 'woocommerce_new_order_item', 'winddoc_appuntamenti_woocommerce_woocommerce_new_order_item', 99, 3 );
function winddoc_appuntamenti_woocommerce_woocommerce_new_order_item( $item_id, $item, $order_id ) {
    $item->update_meta_data( 'id_calendario_attivitacal', $item->legacy_values["id_calendario_attivitacal"] );
    $item->update_meta_data( 'id_calendario_attivitacal_data', $item->legacy_values["id_calendario_attivitacal_data"] );
    $item->save();
}

// stampo i campi nel riepilogo ordine nel backend
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'nap_woocommerce_order_item_get_formatted_meta_data_filter', 10, 2 );
function nap_woocommerce_order_item_get_formatted_meta_data_filter( $formatted_meta, $that ){
    foreach($formatted_meta as $k=>$meta){
        
        if($meta->display_key=="id_calendario_attivitacal"){
            unset($formatted_meta[$k]);
        }
        if($meta->display_key=="id_calendario_attivitacal_data"){
          
          $formatted_meta[$k]->display_key = "Data Appuntamento";
          
          $data = explode(" ",$formatted_meta[$k]->value);  
          $date_time = date(get_option('date_format'),strtotime($data[0]));
          $formatted_meta[$k]->display_value = $date_time." alle ore ".$data[1];
      }
    }
	return $formatted_meta;
}

// stampo i campi nel riepilogo ordine nel frontend ed email
if ( ! function_exists( 'wc_display_item_meta' ) ) {
  //Non stampo il meta booking_config
  function wc_display_item_meta( $item, $args = array() ) {
      $strings = array();
 
      $html    = '';
      $args    = wp_parse_args(
          $args,
          array(
              'before'       => '<ul class="wc-item-meta"><li>',
              'after'        => '</li></ul>',
              'separator'    => '</li><li>',
              'echo'         => true,
              'autop'        => false,
              'label_before' => '<strong class="wc-item-meta-label">',
              'label_after'  => ':</strong> ',
          )
      );
      
      foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
          if($meta->display_key=="id_calendario_attivitacal"){
          }elseif($meta->display_key=="id_calendario_attivitacal_data"){     
             // $value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
              $data = explode(" ",strip_tags($meta->display_value));  
              $date_time = date(get_option('date_format'),strtotime($data[0]));
              $value = $date_time." alle ore ".$data[1];
              $strings[] = $args['label_before'] . "Dapa Appuntamento" . $args['label_after'] . $value;       
          }else{
              $value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
              $strings[] = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;

       

          }
      }

      if ( $strings ) {
          $html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
      }

      $html = apply_filters( 'woocommerce_display_item_meta', $html, $item, $args );
      
      if ( $args['echo'] ) {
         
          echo wp_kses_post($html);
      } else {
          return $html;
      }
  }
}



//Imposto la possibilità di mettere 1 solo quantità del prodotto a carrello
function frontend_action_winddoc_appuntamenti_woocommerce_is_sold_individually( $ret, $product ){
  $_wds = maybe_unserialize(get_post_meta($product->id, '_wd', true));  
  if(is_array($_wds)){
    foreach($_wds as $_wd){    
      if(isset($_wd["id_calendario_attivita"]) &&  $_wd["id_calendario_attivita"]!=""){
        return true;
      }
    }
  }
  return $ret;
}
add_filter( 'woocommerce_is_sold_individually', 'frontend_action_winddoc_appuntamenti_woocommerce_is_sold_individually', 99, 3 );

// Stampo le info della prenotazione nel carrello
add_action( 'woocommerce_after_cart_item_name', 'frontend_action_winddoc_appuntamenti_woocommerce_after_cart_item_name', 99, 3 );
function frontend_action_winddoc_appuntamenti_woocommerce_after_cart_item_name( $cart_item, $cart_item_key  ) {
  if(isset($cart_item["id_calendario_attivitacal_data"])){
    $data = explode(" ",$cart_item["id_calendario_attivitacal_data"]);  
    $date_time = date(get_option('date_format'),strtotime($data[0]));
    echo wp_kses_post("<br>".$date_time." alle ore ".$data[1]);
  }
}




//Devo controllare prima di fare l'ordine se l'appuntamento è ancora disponibile
