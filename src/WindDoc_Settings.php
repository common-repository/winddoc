<?php

class WindDoc_Settings {

  public $param = array("WD_WINDDOC_TOKEN",
                        "WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO",
                        "WD_WINDDOC_ORDINI_ENABLE",
                        "WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI",
                        "WD_WINDDOC_ORDINI_TEMPLATE",
                        "WD_WINDDOC_ORDINI_MAGAZZINO",
                        "WD_WINDDOC_ORDINI_NUMERAZIONE",

                        "WD_WINDDOC_FATTURE_ENABLE",
                        "WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE",
                        "WD_WINDDOC_FATTURE_MAGAZZINO",
                        "WD_WINDDOC_FATTURE_TEMPLATE",                        
                        "WD_WINDDOC_FATTURE_NUMERAZIONE",
                        "WD_WINDDOC_FATTURE_SEND_EMAIL",

                        "WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE",
                        "WD_WINDDOC_RICEVUTE_TEMPLATE",
                        "WD_WINDDOC_RICEVUTE_NUMERAZIONE",

                        "WD_WINDDOC_CONTI_PAGAMENTO",

                        "WD_WINDDOC_PARTITA_IVA",
                        "WD_WINDDOC_CODICE_FISCALE",
                        "WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO",

                        "WD_WINDDOC_APPUNTAMENTI_ENABLE",
                        "WD_WINDDOC_IVA_ZERO",

                      );












  public function __construct() {
	   // Aggiungo la pagina al menu di amministrazione
     add_action( 'admin_menu', array( &$this, 'setupAdminMenus' ) );
	}

  public function setupAdminMenus() {
		
    add_menu_page( 'WindDoc', 'WindDoc', 'manage_options','winddoc_settings', array( &$this, 'settingsPage' ) , "https://app.winddoc.com/theme/default/images/logo_xs.png",56 );
	}

  public function recursive_sanitize_text_field($array) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = $this->recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}

  public function settingsPage() {


    $WindDoc_Helper = new WindDoc_Helper();
    $html = "<h1>Impostazioni WindDoc</h1>";
    if(isset($_POST['update_settings'])){
      foreach ($this->param as $value) {
    		  // Valido l’input
          if(is_array($_POST[$value])){
            $vals = $this->recursive_sanitize_text_field( $_POST[$value]);
            $my_var = json_encode( $vals);
          }else{
            $my_var = sanitize_text_field($_POST[$value]);
          }
       
    		  update_option( $value, $my_var ); // Salvo l’opzione
      }
      $html.='<div id="message" class="updated"><p><strong>Impostazioni salvate.</strong></p></div>';
    }
   
  
    $conti = json_decode(get_option('WD_WINDDOC_CONTI_PAGAMENTO'),true);
   

    $wd_winddoc_list_template_quote = $WindDoc_Helper->getTemplateQuote();
    $wd_winddoc_list_template_invoice = $WindDoc_Helper->getTemplateInvoice();
    $wd_winddoc_list_template_ricevute = $WindDoc_Helper->getTemplateRicevute();
    $wd_winddoc_list_conti = $WindDoc_Helper->getConti();
    $wd_winddoc_list_iva = $WindDoc_Helper->getAliquoteIva();

    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $enabled_gateways = [];

    if( $gateways ) {
        foreach( $gateways as $gateway ) {

            if( $gateway->enabled == 'yes' ) {

                $enabled_gateways[] = $gateway;

            }
        }
    }



    if (!class_exists('WooCommerce')){
      ?>
      <br><div class='updated'><p><?php echo esc_html(__('WindDoc richiede il plugin WooCommerce per essere utilizzato', 'winddoc'));?></p></div>
      <?php 
    }else{ ?>

  		<form method="post" action="">


        

        
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="winddoc_accesso_token">Collega WindDoc</label>
              </th>
              <td>
                <button style="<?php echo esc_attr((get_option('WD_WINDDOC_TOKEN')!="" ? 'display:none' : ''));?>" id="btn_connect_wd" title="Collega Winddoc" type="button" class="button button-primary" onclick="popitwd()" style=""><span><span><span>Collega Winddoc</span></span></span></button>
                <span id="span_wd_connected" style="background:#D5EED4;display:inline-block;color:#338A2E;border-radius: 12px;border: 2px dashed;padding:8px; font-size:17px; <?php echo esc_attr((get_option('WD_WINDDOC_TOKEN')!="" ? '' : 'display:none'))?>"><b>WindDoc Collegato</b></span>
                <button style="<?php echo esc_attr((get_option('WD_WINDDOC_TOKEN')!="" ? '' : 'display:none'));?>" id="btn_disconnect_wd" title="Scollega Winddoc" type="button" class="button button-secondary" onclick="scollegaWD()" style=""><span><span><span>Scollega Winddoc</span></span></span></button>
                <input type="hidden" name="WD_WINDDOC_TOKEN" value="<?php echo esc_attr(get_option('WD_WINDDOC_TOKEN'));?>" id="winddoc_accesso_token">
                <script type="text/javascript">
                function scollegaWD(){
                  document.getElementById("winddoc_accesso_token").value = "";
                  document.getElementById("span_wd_connected").style.display = "none";
                  document.getElementById("btn_disconnect_wd").style.display = "none";
                  document.getElementById("btn_connect_wd").style.display = "inline-block";
                }
                function popitwd() {
                  newwindow=window.open("<?php echo esc_url($WindDoc_Helper->root_login);?>","Login WindDoc","height=400,width=500");
                  if (window.focus) { newwindow.focus(); }
                  return false;
                }
                window.addEventListener("message", receiveMessage, false);
                function receiveMessage(event)
                {

                	if (event.origin == "<?php echo esc_url($WindDoc_Helper->root_login);?>" || event.origin == "<?php echo esc_url($WindDoc_Helper->root);?>"){
                    document.getElementById("winddoc_accesso_token").value = event.data;
                    document.getElementById("span_wd_connected").style.display = "inline";
                    document.getElementById("btn_disconnect_wd").style.display = "inline";
                    document.getElementById("btn_connect_wd").style.display = "none";
                  }
                }
              </script>


  					 </td>
            <tr>
            </table>

          <h2 class="nav-tab-wrapper" id="winddoc-tabs">
          <a style="cursor:pointer;" class="nav-tab nav-tab-active" onclick="jQuery('.tabs-wd').hide();jQuery('#generale').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Generale</a>      
          <a style="cursor:pointer;" class="nav-tab" onclick="jQuery('.tabs-wd').hide();jQuery('#ordini').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Ordini</a>
          
          <a style="cursor:pointer;" class="nav-tab" onclick="jQuery('.tabs-wd').hide();jQuery('#fatture').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Fatture</a>
          <a style="cursor:pointer;" class="nav-tab" onclick="jQuery('.tabs-wd').hide();jQuery('#ricevute').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Ricevute</a>
          <a style="cursor:pointer;" class="nav-tab" onclick="jQuery('.tabs-wd').hide();jQuery('#conti').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Carte e Conti</a>
          
          <a style="cursor:pointer;" class="nav-tab" onclick="jQuery('.tabs-wd').hide();jQuery('#appuntamenti').show();jQuery('#winddoc-tabs a').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');">Appuntamenti</a>
          
          
          
          

          </h2>
          
            <div id="generale" class="tabs-wd">
            <h2>Generale</h2>
      
              <table class="form-table">
              <tbody>


              <tr>
              <th scope="row">
                <label for="WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO">Sincronizza Contatto</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO" id="WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO')==1 ? 'selected' : ''));?>>Si</option>                   
               </select>
               <p class="description">Attiva questa opzione se vuoi sincronizzare l'anagrafica contatto in rubrica.</p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_PARTITAIVA">Partita Iva</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_PARTITA_IVA" id="WD_WINDDOC_PARTITA_IVA" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_PARTITA_IVA')==1 ? 'selected' : ''));?>>Si - Facoltativa</option>
                   <option value="2" <?php echo esc_attr((get_option('WD_WINDDOC_PARTITA_IVA')==2 ? 'selected' : ''));?>>Si - Obbligatoria</option>
               </select>
               <p class="description">Imposta se abilitare il campo partita iva nel checkout</p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_CODICE_FISCALE">Codice Fiscale</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_CODICE_FISCALE" id="WD_WINDDOC_CODICE_FISCALE" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_CODICE_FISCALE')==1 ? 'selected' : ''));?>>Si - Facoltativa</option>
                   <option value="2" <?php echo esc_attr((get_option('WD_WINDDOC_CODICE_FISCALE')==2 ? 'selected' : ''));?>>Si - Obbligatoria</option>
               </select>
               <p class="description">Imposta se abilitare il campo codice fiscale nel checkout</p>
              </td>
            </tr>


            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO">E-mail PEC / Codice destinatario</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO" id="WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_EMAIL_PEC_CODICE_DESTINATARIO')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Imposta se abilitare il campo e-mail PEC e Codice destinatario nel checkout</p>
              </td>
            </tr>
            <th scope="row">
              <label for="">Imposta l'articolo da usare quando non c'è iva</label><br>
            </th>
            <td>
            <select name="WD_WINDDOC_IVA_ZERO" id="WD_WINDDOC_IVA_ZERO" class=" fixed-width-xl">
            <option value=""></option>
               <?php foreach($wd_winddoc_list_iva as $id=>$name) :?>
                
                  <option <?php echo esc_attr((get_option('WD_WINDDOC_IVA_ZERO')==$id ? 'selected' : ''));?> value="<?php echo esc_attr($id);?>"><?php echo esc_html($name);?></option>
                
               <?php endforeach; ?>
            </select>            
            </td>
          </tr>
<tbody>
</table></div>
    

    <div id="ordini" class="tabs-wd" style="display:none;">
        <h2>Ordini</h2>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_ORDINI_ENABLE">Sincronizza ordini</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_ORDINI_ENABLE" id="WD_WINDDOC_ORDINI_ENABLE" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_ORDINI_ENABLE')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Abilita Sincronizzazione ordini clienti</p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI">Tipo sincronizzazione ordini</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI" id="WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI" class=" fixed-width-xl">

                   <option value="0" <?php echo esc_attr((get_option('WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI')==0 ? 'selected' : ''));?>>Manuale</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_ORDINI_SINCRONIZZA_ORDINI')==1 ? 'selected' : ''));?>>Quando viene generato un ordine</option>

               </select>
               <p class="description">Seleziona il metodo di sincronizzazione degli ordini</p>
              </td>
            </tr>


            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_ORDINI_MAGAZZINO">Abilita magazzino</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_ORDINI_MAGAZZINO" id="WD_WINDDOC_ORDINI_MAGAZZINO" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_ORDINI_MAGAZZINO')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Viene scaricato il prodotto da magazzino quando viene emesso un'ordine</p>
              </td>
            </tr>



            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_ORDINI_TEMPLATE">Modello Stampa Ordine</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_ORDINI_TEMPLATE" id="WD_WINDDOC_ORDINI_TEMPLATE" class=" fixed-width-xl">';
              <?php foreach ($wd_winddoc_list_template_quote as $key => $value) :?>
                  <option value="<?php echo esc_attr($key);?>" <?php echo esc_attr((get_option('WD_WINDDOC_ORDINI_TEMPLATE')==$key ? 'selected' : ''));?>><?php echo esc_html($value);?></option>
              <?php endforeach; ?>


               </select>
               <p class="description">Seleziona il modello di stampa dell'ordine.</p>
              </td>
            </tr>



            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_ORDINI_NUMERAZIONE">Numerazione Ordini</label><br>
              </th>
              <td>
              <input type="text" name="WD_WINDDOC_ORDINI_NUMERAZIONE" id="WD_WINDDOC_ORDINI_NUMERAZIONE" value="<?php echo esc_attr(get_option('WD_WINDDOC_ORDINI_NUMERAZIONE'));?>">
               <p class="description">Inserisci il codice di numerazione da attribuire agli ordini generati. Se non viene impostato nessun codice deglio ordini seguiranno la numerazione progressiva</p>
              </td>
            </tr>
        </table>
      </div>

      

      <div id="fatture" class="tabs-wd" style="display:none;">
  
        <h2>Fatture</h2>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_ENABLE">Sincronizza fatture</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_FATTURE_ENABLE" id="WD_WINDDOC_FATTURE_ENABLE" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_ENABLE')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Abilita la gestione della fatturazione di WindDoc</p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE">Tipo sincronizzazione fatture</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE" id="WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE" class=" fixed-width-xl">

                   <option value="0" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==0 ? 'selected' : ''));?>>Manuale</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_SINCRONIZZA_FATTURE')==1 ? 'selected' : ''));?>>Quando un ordine è contrassegnato come pagato</option>

               </select>
               <p class="description">Seleziona il metodo di sincronizzazione delle fatture</p>
              </td>
            </tr>


            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_MAGAZZINO">Abilita magazzino</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_FATTURE_MAGAZZINO" id="WD_WINDDOC_FATTURE_MAGAZZINO" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_MAGAZZINO')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Viene scaricato il prodotto da magazzino quando viene emessa una fattura</p>
              </td>
            </tr>



            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_TEMPLATE">Modello Stampa Fatture</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_FATTURE_TEMPLATE" id="WD_WINDDOC_FATTURE_TEMPLATE" class=" fixed-width-xl">';
              <?php foreach ($wd_winddoc_list_template_invoice as $key => $value) :?>
                <option value="<?php echo esc_attr($key);?>" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_TEMPLATE')==$key ? 'selected' : ''));?>><?php echo esc_html($value);?></option>
              <?php endforeach;?>
               </select>
               <p class="description">Seleziona il modello di stampa della fattura.</p>
              </td>
            </tr>


            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_NUMERAZIONE">Numerazione fatture</label><br>
              </th>
              <td>
              <input type="text" name="WD_WINDDOC_FATTURE_NUMERAZIONE" id="WD_WINDDOC_FATTURE_NUMERAZIONE" value="<?php echo esc_attr(get_option('WD_WINDDOC_FATTURE_NUMERAZIONE'));?>">
               <p class="description">Inserisci il codice di numerazione da attribuire alle fatture generate. Se non viene impostato nessun codice le fatture seguiranno la numerazione progressiva</p>
              </td>
            </tr>




            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_FATTURE_SEND_EMAIL">Invia Email</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_FATTURE_SEND_EMAIL" id="WD_WINDDOC_FATTURE_SEND_EMAIL" class=" fixed-width-xl">
                   <option value="0">No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_FATTURE_SEND_EMAIL')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Invia l'email al cliente quando viene emessa la fattura.</p>
              </td>
            </tr>
        </table>


        </div>
        <div id="ricevute" class="tabs-wd" style="display:none;">

        <h2>Ricevute</h2>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE">Crea Ricevute</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE" id="WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE" class=" fixed-width-xl">
                   <option value="0" <?php echo esc_attr((get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==0 ? 'selected' : ''));?>>No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Abilita la gestione della ricevuta in WindDoc quando il cliente non ha la partita iva.</p>
              </td>
            </tr>


            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_RICEVUTE_TEMPLATE">Modello Stampa Ricevute</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_RICEVUTE_TEMPLATE" id="WD_WINDDOC_RICEVUTE_TEMPLATE" class=" fixed-width-xl">';
              <?php foreach ($wd_winddoc_list_template_ricevute as $key => $value) :?>
                <option value="<?php echo esc_attr($key);?>" <?php echo esc_attr((get_option('WD_WINDDOC_RICEVUTE_TEMPLATE')==$key ? 'selected' : ''));?>><?php echo esc_html($value);?></option>
              <?php endforeach;?>


               </select>
               <p class="description">Seleziona il modello di stampa della ricevuta.</p>
              </td>
            </tr>




            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_RICEVUTE_NUMERAZIONE">Numerazione ricevute</label><br>
              </th>
              <td>
              <input type="text" name="WD_WINDDOC_RICEVUTE_NUMERAZIONE" id="WD_WINDDOC_RICEVUTE_NUMERAZIONE" value="<?php echo esc_attr(get_option('WD_WINDDOC_RICEVUTE_NUMERAZIONE'));?>">
               <p class="description">Inserisci il codice di numerazione da attribuire alle ricevute generate. Se non viene impostato nessun codice le ricevute seguiranno la numerazione progressiva.</p>
              </td>
            </tr>


        </table>
        </div>


        <div id="conti" class="tabs-wd" style="display:none;">
          <h2>Seleziona per ogni pagamento il conto da associare a WindDoc</h2>
          
          <?php foreach($enabled_gateways as $ge):?>                        

            <table class="form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    <label for="WD_WINDDOC_CONTI_PAGAMENTO">Pagamento <?php echo $ge->title;?></label><br>
                  </th>
                  <td>
                    <select name="WD_WINDDOC_CONTI_PAGAMENTO[<?php echo $ge->id;?>]" id="WD_WINDDOC_CONTI_PAGAMENTO<?php echo $ge->id;?>" class=" fixed-width-xl">';
                      <?php foreach ($wd_winddoc_list_conti as $key => $value) :?>
                        <option value="<?php echo esc_attr($key);?>" <?php echo esc_attr((isset($conti[$ge->id]) && $conti[$ge->id]==$key ? 'selected' : ''));?>><?php echo esc_html($value);?></option>
                      <?php endforeach;?>
                    </select>
                    <p class="description">Nel caso in sui sia selezionato il contro Predefinito sarà utilizzato il conto predefinito impostato in WindDoc.</p>
                  </td>
                </tr>
              </tbody>
            </table>          
          <?php endforeach;?>
        </div>
        <div id="appuntamenti" class="tabs-wd" style="display:none;">

        <h2>Appuntamenti</h2>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="WD_WINDDOC_APPUNTAMENTI_ENABLE">Abilita Gestione Appuntamenti</label><br>
              </th>
              <td>
              <select name="WD_WINDDOC_APPUNTAMENTI_ENABLE" id="WD_WINDDOC_APPUNTAMENTI_ENABLE" class=" fixed-width-xl">
                   <option value="0" <?php echo esc_attr((get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==0 ? 'selected' : ''));?>>No</option>
                   <option value="1" <?php echo esc_attr((get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1 ? 'selected' : ''));?>>Si</option>
               </select>
               <p class="description">Abilita la gestione di prenotazione degli appuntamenti.</p>
              </td>
            </tr>


        </table>
        </div>


  		<input type="submit" value="Save" class="button button-primary" />
  		<input type="hidden" name="update_settings" value="1" /></p>
  		</form>
      <?php
    }
  }
}
