<?php

class WindDoc_Helper{

    public $root_login = 'https://app.winddoc.com/oauth-login.php?token_app=9ecb6d8e62357d14b1cc7a5450a62e9b557982f753d5f38f1fd02024613bdd91';
    public $root = 'https://app.winddoc.com';


    public function creaOrdine($order,$force_payment=false){
      $dt = $this->dettaglioOrdine($order);

      if(!isset($dt[0]) || !isset($dt[0]->url_ordine_winddoc) || !$dt[0]->url_ordine_winddoc!=""){
        $data = $this->getDataFromOrder($order,"ordini",$force_payment);

        $verifica = false;
        $id_calendario_attivitacal = array();
        if(isset($dt[0]) && ($dt[0]->id_ordine_winddoc!="" || $dt[0]->id_invoice_winddoc!="")){
        }else{        
          //Verifica se posso prenotare appuntamento
          if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){       
            
            if(count($id_calendario_attivitacal)>0){
              $verifica = true;
              foreach($id_calendario_attivitacal as $id_cal){
                if($verifica){
                  $WindDoc_Helper= new WindDoc_Helper();
                  $verifica = $WindDoc_Helper->verificaAppuntamento($id_cal);    
                }                          
              }
            }
            if(!$verifica){
              $ret["success"] = false;
              $ret["message"] = 'Non è stato possibile prenotare l\'appuntamento in winddoc';
              return $ret;
            }
          }
        }

        $WindDocTalker = new WindDocTalker();

        $data = $WindDocTalker->sendDocument($data,$data["document_type"]);

        if(!isset($data["id"])){
          $ret["success"] = false;
          $ret["message"] = 'Non è stato possibile creare l\'ordine in WindDoc';

        }else{
          $this->aggiungiWinDocToOrder($order,$data["id"],$data["url"]);
          $ret["success"] = true;
          $ret["message"] = 'Ordine Creato correttamente in WindDoc.';

          if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){

            if(count($id_calendario_attivitacal)>0){
              foreach($id_calendario_attivitacal as $id_cal){
                $WindDoc_Helper= new WindDoc_Helper();
                $WindDoc_Helper->prenotaAppuntamento($id_cal,$data["id"],"");
                
              }
            }    
          }

        }
      }else{
        $ret["success"] = false;
        $ret["message"] = 'Ordine già creato in WindDoc';
      }
      return $ret;
    }

    public function creaFattura($order,$force_payment=false){
      $dt = $this->dettaglioOrdine($order);
      if(!isset($dt[0]) || !isset($dt[0]->url_invoice_winddoc) || !$dt[0]->url_invoice_winddoc!=""){

       
        if(get_option('WD_WINDDOC_FATTURE_ENABLE')!=0){
          $data = $this->getDataFromOrder($order,"fatture",$force_payment);
        }
        if(get_option('WD_WINDDOC_FATTURE_ENABLE')==0 && get_option('WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE')!=0){
          $data = $this->getDataFromOrder($order,"ricevute",$force_payment);
        }

       

        if(isset($dt["id_ordine_winddoc"]) && $dt["id_ordine_winddoc"]!=""){
          $data["relation"][0]["parent_type"] = "ordini";
          $data["relation"][0]["parent_id"] = $dt["id_ordine_winddoc"];
        }

        //verifico se devo inviare l'email
        if(get_option('WD_WINDDOC_FATTURE_SEND_EMAIL')==1){
          $data["send_email"] = true;
        }
        
        
       

        $id_calendario_attivitacal = array();
        if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){          
          $order_data = new \WC_Order($order);
          foreach ($order_data->get_items('line_item') as $item){      
            $data_meta = $item->get_meta_data();      
            foreach($data_meta as $dd){
                if($dd->key=="id_calendario_attivitacal"){                                                                                
                  $id_calendario_attivitacal[] = $dd->value;
                }
            }
          }
        }

        $verifica = false;
        if(isset($dt[0]) && ($dt[0]->id_ordine_winddoc!="" || $dt[0]->id_invoice_winddoc!="")){
        }else{        
          //Verifica se posso prenotare appuntamento
          if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){       
            
            if(count($id_calendario_attivitacal)>0){
              $verifica = true;
              foreach($id_calendario_attivitacal as $id_cal){
                if($verifica){
                  $WindDoc_Helper= new WindDoc_Helper();
                  $verifica = $WindDoc_Helper->verificaAppuntamento($id_cal);    
                }                          
              }
            }
            if(!$verifica){
              $ret["success"] = false;
              $ret["message"] = 'Non è stato possibile prenotare l\'appuntamento in winddoc';
              return $ret;
            }
          }
        }
        
       
        $WindDocTalker = new WindDocTalker();

        $data = $WindDocTalker->sendDocument($data,$data["document_type"]);
       
        $ret = array();
        if(!isset($data["id"])){
          $ret["success"] = false;
          $ret["message"] = 'Non è stato possibile creare la fattura in WindDoc';
        }else{
          $this->aggiungiWinDocToInvoice($order,$data["id"],$data["url"]);
          $ret["success"] = true;
          $ret["message"] = 'Fattura Creata correttamente in WindDoc.';

          if(get_option('WD_WINDDOC_APPUNTAMENTI_ENABLE')==1){            
            if(count($id_calendario_attivitacal)>0){
              foreach($id_calendario_attivitacal as $id_cal){
                $WindDoc_Helper= new WindDoc_Helper();
                $WindDoc_Helper->prenotaAppuntamento($id_cal,"",$data["id"]);                
              }
            }    
          }  
        }
      }else{
        $ret["success"] = false;
        $ret["message"] = 'Fattura già creata in WindDoc';
      }
      return $ret;
    }



    public function aggiungiWinDocToOrder($sales_flat_order_id,$id_ordine_winddoc,$url_ordine_winddoc){
      if(!$this->dettaglioOrdine($sales_flat_order_id)){
          $this->addNewOrder($sales_flat_order_id);
      }


      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_order';
      $wpdb->update($table_name,array("url_ordine_winddoc"=>$url_ordine_winddoc,
                                      "id_ordine_winddoc"=>$id_ordine_winddoc,
                                      "data_modifica"=>current_time( 'mysql' )),
                                array("sales_flat_order_id"=>$sales_flat_order_id));


    }

    public function aggiungiWinDocToInvoice($sales_flat_order_id,$id_invoice_winddoc,$url_invoice_winddoc){
      if(!$this->dettaglioOrdine($sales_flat_order_id)){
          $this->addNewOrder($sales_flat_order_id);
      }


      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_order';
      $wpdb->update($table_name,array("url_invoice_winddoc"=>$url_invoice_winddoc,
                                      "id_invoice_winddoc"=>$id_invoice_winddoc,
                                      "data_modifica"=>current_time( 'mysql' )),
                                array("sales_flat_order_id"=>$sales_flat_order_id));

    }

    public function getIdCustomerWinddoc($id_customer){
      $det = $this->dettaglioSincroCustomer($id_customer);
      if(isset($det["id_customer_winddoc"])){
        return $det["id_customer_winddoc"];
      }
      return false;
    }

    public function dettaglioOrdine($sales_flat_order_id = ""){
      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_order';
      $det = $wpdb->get_results("SELECT * FROM `" . $table_name . "` WHERE `sales_flat_order_id` = '" .$sales_flat_order_id . "'");

      if(isset($det)){
        return $det;
      }
      return false;
    }


    public function dettaglioSincroCustomer($id_customer){
      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_order';
      $det = $wpdb->get_results("SELECT * FROM `" . $table_name."` WHERE `customer_id` = '" .$id_customer . "'");
      if(isset($det)){
        return $det;
      }
      return false;

    }

    public function aggiungiSincroCustomer($customer_id,$id_customer_winddoc){

      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_customer';
      $wpdb->insert($table_name,array("customer_id"=>$customer_id,
                                      "id_customer_winddoc"=>$id_customer_winddoc,
                                      "data_creazione"=>current_time( 'mysql' ),
                                      "data_modifica"=>current_time( 'mysql' )));

    }

    public function addNewOrder($id){
      global $wpdb;
      $table_name = $wpdb->prefix.'winddoc_sinc_order';
      $wpdb->insert($table_name,array("sales_flat_order_id"=>$id,
                                      "data_creazione"=>current_time( 'mysql' ),
                                      "data_modifica"=>current_time( 'mysql' )));

    }

    public function getDataFromOrder($id_order,$type="",$force_payment=false){




      $order = new \WC_Order($id_order);
      $totale_calcolo = 0;


      $data = array();
      $data["document_type"] = $type;
      if($type=="fatture"){
        $data["numerazione"] = get_option("WD_WINDDOC_FATTURE_NUMERAZIONE");
      }
      if($type=="ricevute"){
        $data["numerazione"] = get_option("WD_WINDDOC_RICEVUTE_NUMERAZIONE");
      }
      if($type=="ordini"){
        $data["numerazione"] = get_option("WD_WINDDOC_ORDINI_NUMERAZIONE");
      }
      $data["valuta"] = "e994378e-c24b-12e2-cb41-5717c48cdc99"; //Impostiamo di defaut €

      //template_stampa
      if($type=="fatture"){
        $data["template_stampa"] = get_option("WD_WINDDOC_FATTURE_TEMPLATE");
        if($order->get_order_number()!=""){
          $data["dicitura_invoice"] = "Riferimento Ordine #".$order->get_order_number();
        }else{
          $data["dicitura_invoice"] = "Riferimento Ordine #".$id_order;
        }
      }
      if($type=="ordini"){
        $data["template_stampa"] = get_option("WD_WINDDOC_ORDINI_TEMPLATE");
        if($order->get_order_number()!=""){
          $data["dicitura_quote"] = "Riferimento Ordine #".$order->get_order_number();
        }else{
          $data["dicitura_quote"] = "Riferimento Ordine #".$id_order;
        }
      }
      if($type=="ricevute"){
        $data["template_stampa"] = get_option("WD_WINDDOC_RICEVUTE_TEMPLATE");
        if($order->get_order_number()!=""){
          $data["dicitura_invoice"] = "Riferimento Ordine #".$order->get_order_number();
        }else{
          $data["dicitura_invoice"] = "Riferimento Ordine #".$id_order;
        }
      }


      $indirizzo_fatturazione = array();
      $indirizzo_fatturazione["contatto_nome"] = trim($order->data["billing"]["first_name"]);
      $indirizzo_fatturazione["contatto_cognome"] = trim($order->data["billing"]["last_name"]);
      $indirizzo_fatturazione["contatto_codice_fiscale"] = $this->parseMetadata($order,"_billing_codice_fiscale");
      $indirizzo_fatturazione["contatto_partita_iva"] = $this->parseMetadata($order,"_billing_partita_iva");
      $indirizzo_fatturazione["contatto_codice_destinatario"] = $this->parseMetadata($order,"_billing_codice_destinatario");
      $indirizzo_fatturazione["contatto_email_pec"] = $this->parseMetadata($order,"_billing_pec");
      $indirizzo_fatturazione["contatto_ragione_sociale"] = $order->data["billing"]["company"];
      if($indirizzo_fatturazione["contatto_nome"]==""){
        $indirizzo_fatturazione["contatto_nome"] = $order->data["billing"]["company"];
      }
      $indirizzo_fatturazione["contatto_indirizzo_via"] = trim($order->data["billing"]["address_1"]." ".$order->data["billing"]["address_2"]);
      $indirizzo_fatturazione["contatto_indirizzo_citta"] = $order->data["billing"]["city"];
      $indirizzo_fatturazione["contatto_indirizzo_cap"] = $order->data["billing"]["postcode"];
      $indirizzo_fatturazione["contatto_indirizzo_provincia"] = $order->data["billing"]["state"];
      $indirizzo_fatturazione["contatto_indirizzo_nazione"] = $order->data["billing"]["country"];
      $indirizzo_fatturazione["contatto_email"] = $order->data["billing"]["email"];
      $indirizzo_fatturazione["contatto_cellulare"] = $order->data["billing"]["phone"];

      if(isset($order->data["billing"]["phone"]) && $order->data["billing"]["phone"]!=""){
        $data["contatto_indirizzo_extra"] = "Telefono : ".$order->data["billing"]["phone"];
      }





      $indirizzo_spedizione = array();
      $indirizzo_spedizione["contatto_nome"] = trim($order->data["shipping"]["first_name"]);
      $indirizzo_spedizione["contatto_cognome"] = trim($order->data["shipping"]["last_name"]);
      $indirizzo_spedizione["contatto_ragione_sociale"] = $order->data["shipping"]["company"];
      if($indirizzo_spedizione["contatto_nome"]==""){
        $indirizzo_spedizione["contatto_nome"] = $order->data["shipping"]["company"];
      }
      $indirizzo_spedizione["contatto_indirizzo_via"] = trim($order->data["shipping"]["address_1"]." ".$order->data["shipping"]["address_2"]);
      $indirizzo_spedizione["contatto_indirizzo_citta"] = $order->data["shipping"]["city"];
      $indirizzo_spedizione["contatto_indirizzo_cap"] = $order->data["shipping"]["postcode"];
      $indirizzo_spedizione["contatto_indirizzo_provincia"] = $order->data["shipping"]["state"];
      $indirizzo_spedizione["contatto_indirizzo_nazione"] = $order->data["shipping"]["country"];
      $indirizzo_spedizione["contatto_cellulare"] = $order->data["shipping"]["phone"];




      if($indirizzo_fatturazione["contatto_partita_iva"]=="" && $indirizzo_fatturazione["contatto_codice_fiscale"]!=""){
        //E' un privato
        if($type=="fatture"){
          if(get_option("WD_WINDDOC_RICEVUTE_SINCRONIZZA_RICEVUTE")==1){
            $data["numerazione"] = get_option("WD_WINDDOC_ORDINI_NUMERAZIONE");
            $data["document_type"] = "ricevute";
            $type = "ricevute";
          }
        }
      }
      if($indirizzo_fatturazione["contatto_codice_destinatario"]==""){
        if($indirizzo_fatturazione["contatto_indirizzo_nazione"]=="" || $indirizzo_fatturazione["contatto_indirizzo_nazione"]=="IT"){
          $indirizzo_fatturazione["contatto_codice_destinatario"] = "0000000";
        }else{
          $indirizzo_fatturazione["contatto_codice_destinatario"] = "9999999";
        }
        
      }
      
      $data["contatto_nome"] = $indirizzo_fatturazione["contatto_nome"];
      $data["contatto_cognome"] = $indirizzo_fatturazione["contatto_cognome"];
      $data["contatto_codice_fiscale"] = $indirizzo_fatturazione["contatto_codice_fiscale"];
      $data["contatto_ragione_sociale"] = $indirizzo_fatturazione["contatto_ragione_sociale"];
      $data["contatto_indirizzo_citta"] = $indirizzo_fatturazione["contatto_indirizzo_citta"];
      $data["contatto_indirizzo_nazione"] = $indirizzo_fatturazione["contatto_indirizzo_nazione"];
      $data["contatto_indirizzo_cap"] = $indirizzo_fatturazione["contatto_indirizzo_cap"];
      $data["contatto_indirizzo_provincia"] = $indirizzo_fatturazione["contatto_indirizzo_provincia"];
      $data["contatto_indirizzo_via"] = $indirizzo_fatturazione["contatto_indirizzo_via"];
      $data["contatto_partita_iva"] = $indirizzo_fatturazione["contatto_partita_iva"];
      $data["contatto_email"] = $indirizzo_fatturazione["contatto_email"];
      $data["contatto_codice_destinatario"] = $indirizzo_fatturazione["contatto_codice_destinatario"];
      $data["contatto_email_pec"] = $indirizzo_fatturazione["contatto_email_pec"];

      if( $indirizzo_spedizione["contatto_nome"] != ""  ||
          $indirizzo_spedizione["contatto_ragione_sociale"] != ""  ||
          $indirizzo_spedizione["contatto_indirizzo_via"] != ""  ||
          $indirizzo_spedizione["contatto_indirizzo_citta"] != ""  ||
          $indirizzo_spedizione["contatto_indirizzo_cap"] != ""  ||
          $indirizzo_spedizione["contatto_indirizzo_provincia"] != ""  ||
          $indirizzo_spedizione["contatto_indirizzo_nazione"] != ""
        ){
        if( $indirizzo_spedizione["contatto_nome"] != $indirizzo_fatturazione["contatto_nome"]  ||
            $indirizzo_spedizione["contatto_ragione_sociale"] != $indirizzo_fatturazione["contatto_ragione_sociale"]  ||
            $indirizzo_spedizione["contatto_indirizzo_via"] != $indirizzo_fatturazione["contatto_indirizzo_via"]  ||
            $indirizzo_spedizione["contatto_indirizzo_citta"] != $indirizzo_fatturazione["contatto_indirizzo_citta"]  ||
            $indirizzo_spedizione["contatto_indirizzo_cap"] != $indirizzo_fatturazione["contatto_indirizzo_cap"]  ||
            $indirizzo_spedizione["contatto_indirizzo_provincia"] != $indirizzo_fatturazione["contatto_indirizzo_provincia"]  ||
            $indirizzo_spedizione["contatto_indirizzo_nazione"] != $indirizzo_fatturazione["contatto_indirizzo_nazione"]
          ){
            $data["cointestatario"][0] = array();
            $data["cointestatario"][0]["nome"] = $indirizzo_spedizione["contatto_nome"];
            $data["cointestatario"][0]["cognome"] = $indirizzo_spedizione["contatto_cognome"];
            $data["cointestatario"][0]["codice_fiscale"] = "";
            $data["cointestatario"][0]["ragione_sociale"] = $indirizzo_spedizione["contatto_ragione_sociale"];
            $data["cointestatario"][0]["indirizzo_citta"] = $indirizzo_spedizione["contatto_indirizzo_citta"];
            $data["cointestatario"][0]["indirizzo_nazione"] = $indirizzo_spedizione["contatto_indirizzo_nazione"];

            $data["cointestatario"][0]["indirizzo_cap"] = $indirizzo_spedizione["contatto_indirizzo_cap"];
            $data["cointestatario"][0]["indirizzo_provincia"] = $indirizzo_spedizione["contatto_indirizzo_provincia"];

            $data["cointestatario"][0]["indirizzo_via"] = $indirizzo_spedizione["contatto_indirizzo_via"];
            $data["cointestatario"][0]["partita_iva"] = "";
            $data["cointestatario"][0]["email"] = "";
            $data["cointestatario"][0]["codice_destinatario"] = "";
            $data["cointestatario"][0]["email_pec"] = "";
        }
      }

      $items = array();
      foreach ($order->get_items('line_item') as $item){
        $items[] = $item;
      }
      foreach ($order->get_items('shipping') as $item){
        $items[] = $item;
      }
      foreach ($order->get_items('fee') as $item){
        $items[] = $item;
      }

      
      
      $totale = 0;
      $vatAmount = 0;
      $totalWithoutTax = 0;

      $data["prodotto"] = array();
      $iva_totale = array();

      if($order->get_customer_note()!=""){
        $prodotto["tipo_riga"] = 1; //Tipo riga Descrizione
        $prodotto["nome"] = "Note Cliente";
        $prodotto["note"] = $order->get_customer_note();
       
        
      }


    /*  foreach ($items as $item){
        if($item->get_total() != 0){
          $vat = round(($item->get_total_tax() / $item->get_total()),2)*100;
          $nome_iva = trim(' '.$vat);
          $total_tax = round($item->get_total_tax(),2);
          if(isset($nome_iva)){
            $iva_totale[$nome_iva] = $iva_totale[$nome_iva] + $total_tax;
          }else{
            $iva_totale[$nome_iva] = $total_tax;
          }
        }
      }*/

      foreach ($items as $item){
        $prodotto = array();
        
     

        $prodotto["importo_totale"] = round($item->get_total()+$item->get_total_tax(),2);

        if($item->get_type() == 'line_item'){

          $prodotto["tipo_riga"] = 0; //Tipo riga prodotto
          $prodotto["quantita"] = $item->get_quantity();
          $prodotto["prezzo_netto"] = round($item->get_total()/$prodotto["quantita"],2);

          $prodotto["codice"] = $item->get_product()->get_sku();
          $prodotto["nome"] = $item->get_name();
          if($prodotto["codice"]==""){
            //PER EBAY
            $prodotto["codice"] = wc_get_order_item_meta($item->get_id(), "SKU", true);
          }

          $prodotto["unita_misura"] = "pz";
          $prodotto["sconto"] = 0;
          $prodotto["iva_importo"] = 0;
          if($item->get_total()>0){
            $prodotto["iva_importo"] = round((($item->get_total_tax() / $item->get_total())*100),2);
          }

          if($prodotto["iva_importo"]==0 && get_option('WD_WINDDOC_IVA_ZERO')!=""){
            $prodotto["iva"] = get_option('WD_WINDDOC_IVA_ZERO');
          }

          $prodotto["iva_name"] = "";

          if($type=="fatture" || $type=="ricevute"){
            if(get_option("WD_WINDDOC_FATTURE_MAGAZZINO")==1){
              $prodotto["scarica_da_magazzino"] = 1;
            }
          }
          if($type=="ordini"){
            if(get_option("WD_WINDDOC_ORDINI_MAGAZZINO")==1){
              $prodotto["scarica_da_magazzino"] = 1;
            }
          }


       
          $data_meta = $item->get_meta_data();      
          
          foreach($data_meta as $dd){
              if($dd->key=="id_calendario_attivitacal_data"){                                                                                
                $prodotto["note"] ="Appuntamento del ".$dd->value;
              }
          }
          

          //$sconto =  $item->get_subtotal() - $item->get_total()
         }elseif($item->get_type()=='fee' || $item->get_type()=='shipping'){
           $prodotto["quantita"] = 1;
           $prodotto["tipo_riga"] = 2; //Tipo riga servizio
           $prodotto["prezzo_netto"] = $item->get_total();

           $prodotto["codice"] = "";
           $prodotto["nome"] = $item->get_name();
           $prodotto["unita_misura"] = "pz";
           $prodotto["sconto"] = 0;
           $prodotto["iva_importo"] = 0;
           if($item->get_total()>0){
            $prodotto["iva_importo"] = round((($item->get_total_tax() / $item->get_total())*100),2);
           }
           $prodotto["iva_name"] = "";
           if($prodotto["iva_importo"]==0 && get_option('WD_WINDDOC_IVA_ZERO')!=""){
            $prodotto["iva"] = get_option('WD_WINDDOC_IVA_ZERO');
            }
       }

       $data["prodotto"][] = $prodotto;
       $totale_calcolo = $totale_calcolo  + $prodotto["importo_totale"];
      }



      $data["scadenza"] = array();
      $data["scadenza"][0]["ammontare"] = $totale_calcolo;
      $data["scadenza"][0]["termini"] = "0"; //Immediato
      $data["scadenza"][0]["scadenza"] = time();

      $payment_method = $order->get_payment_method();

      if($force_payment && $payment_method!="cod" && $payment_method=="ppec_paypal"){
        $data["scadenza"][0]["id_conto"] ="non_saldato";
      }else{
        $conti = json_decode(get_option('WD_WINDDOC_CONTI_PAGAMENTO'),true);
        if(isset($conti[$payment_method])){
          $data["scadenza"][0]["id_conto"] = $conti[$payment_method];
          if($data["scadenza"][0]["id_conto"]!="0"){
            $data["scadenza"][0]["data_saldo"] = time();
          }
        }
      
       
      }

      if(get_option('WD_WINDDOC_CONTATTI_SINCRONIZZA_CONTATTO')==1){
        $data["aggiungi_contatto"] = true;
        $data["aggiungi_contatto_search"] = true;
      }

      return $data;

    }

    public function parseMetadata($order,$name){
      return get_post_meta($order->get_id(), $name, true);
    }



    public function getConti(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getConti();
      $ret = array();
      $ret["predefinito"] = "Predefinito";

      if(isset($template["lista"]) && count($template["lista"])>0){
        foreach ($template["lista"] as $value) {
  
          $ret[$value["id_conto"]] = $value["nome"];
        }
      }

      return $ret;
    }

    public function getAliquoteIva(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getAliquoteIva();
      $ret = array();
     

      if(isset($template["lista"]) && count($template["lista"])>0){
        foreach ($template["lista"] as $value) {
  
          $ret[$value["id_iva"]] = $value["nome"];
        }
      }

      return $ret;
    }


    public function getTemplateRicevute(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getTemplateRicevute();
     
      $ret = array();
      $ret["predefinito"] = "Predefinito";
      if(isset($template["lista"]) && count($template["lista"])>0){
        foreach ($template["lista"] as $value) {
          
          $ret[$value["id_templatestampa"]] = $value["nome"];
        }
      }
      return $ret;
    }

    public function getTemplateInvoice(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getTemplateInvoice();
      $ret = array();
      $ret["predefinito"] = "Predefinito";
      if(isset($template["lista"]) && count($template["lista"])>0){
        foreach ($template["lista"] as $value) {
          $ret[$value["id_templatestampa"]] = $value["nome"];
        }
      }
      return $ret;
    }

    public function getTemplateQuote(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getTemplateQuote();
   
      $ret = array();
      $ret["predefinito"] = "Predefinito";
      if(isset($template["lista"]) && count($template["lista"])>0){
        foreach ($template["lista"] as $value) {
          $ret[$value["id_templatestampa"]] = $value["nome"];
        }
      }
      return $ret;
    }


    public function getCalendariAttivita(){
      $WindDocTalker = new WindDocTalker();
      $template = $WindDocTalker->getCalendariAttivita();

      
      $ret = array();
      if(isset($template["lista"]) && count($template["lista"])>0){        
        foreach ($template["lista"] as $value) {
          $ret[$value["id_calendario_attivita"]] = $value["nome"];
        }
        
      }

      return $ret;
    }

    
    public function verificaAppuntamento($id_calendario_attivitacal){
      $WindDocTalker = new WindDocTalker();
      $app = $WindDocTalker->InfoAppuntamento($id_calendario_attivitacal);

      $rets = $WindDocTalker->getAttivitaCalAllDisponibili($app["id_calendario_attivita"], $app["data_inizio"], $app["data_fine"]);
      foreach($rets as $ret){
        if($ret["id_calendario_attivitacal"] == $id_calendario_attivitacal){
          return true;
        }
      }
      return false;
      //getAttivitaCalAllDisponibili
    }

    public function getDettAttivita($id_calendario_attivita){
      $WindDocTalker = new WindDocTalker();
      return $WindDocTalker->getCalendarioAttivita($id_calendario_attivita);
      
    }

    public function getBookingFromTo($id_calendario_attivita, $from="", $to=""){
      $WindDocTalker = new WindDocTalker();
      $ret = $WindDocTalker->getAttivitaCalDisponibili($id_calendario_attivita, $from, $to);
      return $ret;
    }

    public function setAppuntamentoOpzionato($id_calendario_attivitacal){
      $WindDocTalker = new WindDocTalker();
      $WindDocTalker->modificaAttivitaCal($id_calendario_attivitacal,array("stato"=>3));
    }

    public function prenotaAppuntamento($id_calendario_attivitacal,$id_quote="",$id_invoice=""){
      $WindDocTalker = new WindDocTalker();
      $WindDocTalker->prenotaAppuntamento($id_calendario_attivitacal,$id_quote,$id_invoice);
    }

    public function ripristinaAppuntamento($id_calendario_attivitacal){
      //recupero lo stato... se è opzionato lo posso modificare in attivo annullando l'appuntamento
      //Se ho già fatto la fattura non lo annullo
      $WindDocTalker = new WindDocTalker();
      $info = $WindDocTalker->InfoAppuntamento($id_calendario_attivitacal);
      if($info["id_invoice"]==""){
        $WindDocTalker->annullaAppuntamento($id_calendario_attivitacal);
      }
    }
}




class WindDocTalker
{
  private $url;
  private $soapclient;
  private $sessionId;
  private $token_app = "9ecb6d8e62357d14b1cc7a5450a62e9b557982f753d5f38f1fd02024613bdd91";

  function __construct(){
    $this->url = 'https://app.winddoc.com/v1/api_json.php';
    
  }


  public function sendDocument($data,$tipo){
      
    $args["params"] = $data;

    if($tipo=="fatture"){
      $ret = $this->__call("fatture_aggiungi", $args);
      return $ret;
    }
    if($tipo=="ricevute"){
      $ret = $this->__call("ricevute_aggiungi", $args);
      return $ret;
    }
    if($tipo=="ordini"){
      $ret = $this->__call("ordini_aggiungi", $args);
      return $ret;
    }      
  }

  public function aggiungiCustomer($data){
    
    $args["params"] = $data;
    $ret = $this->__call("contatti_aggiungi", $args);

    return $ret;    
  }

  public function getAliquoteIva(){
    $args = array();
    $args["query"] = "";
    $args["pagina"] = "1";
    $args["order"] = "nome asc";
    $args["limit_list"] = "200";

    $ret = $this->__call("tabelle_iva_lista", $args);
    
    return $ret;
  }

  public function getConti(){
    
      
      $args = array();
      $args["query"] = "";
      $args["pagina"] = "1";
      $args["order"] = "";
      $args["length"] = "100";

      $ret = $this->__call("settings_conti_lista", $args);
      
      return $ret;
    
  }


  public function getTemplateRicevute(){
   
      $args = array();   
      $args["pagina"] = "1";      
      $ret = $this->__call("ricevute_listaTemplate", $args);
      
      return $ret;
    
  }


  public function getTemplateQuote(){
      $args = array();
      $args["pagina"] = "1";  
      $ret = $this->__call("ordini_listaTemplate", $args);
     
      return $ret;
    
  }

  public function getTemplateInvoice(){
      $args = array();    
      $args["pagina"] = "1";   
      $ret = $this->__call("fatture_listaTemplate", $args);
     
      return $ret;    
  }


  public function getCalendariAttivita(){
    $args = array();    
    
    $args["query"] = "";
    $args["pagina"] = "1";
    $args["order"] = "";
    $args["length"] = "100";

    $ret = $this->__call("calendari_attivita_lista", $args);
   
    return $ret;    
  }
  
  public function getCalendarioAttivita($id_calendario_attivita ){
    $args = array();   
    $args["id"] = $id_calendario_attivita;      
    $ret = $this->__call("calendari_attivita_dettaglio", $args);
    
    return $ret;
  }

  public function getAttivitaCalAllDisponibili($id_calendario_attivita,$from="",$to=""){
    
      
    $args = array();
    $args["id_calendario_attivita"] = $id_calendario_attivita;
    $args["from"] = $from;
    $args["to"] = $to;
    

    $ret = $this->__call("calendari_attivitacal_disponibiliall", $args);
    
    return $ret;
  
  }

  public function getAttivitaCalDisponibili($id_calendario_attivita,$from="",$to=""){
    
      
    $args = array();
    $args["id_calendario_attivita"] = $id_calendario_attivita;
    $args["from"] = $from;
    $args["to"] = $to;
    

    $ret = $this->__call("calendari_attivitacal_disponibili", $args);
    
    return $ret;
  
  }

  public function prenotaAppuntamento($id_calendario_attivitacal,$id_quote="",$id_invoice=""){
    
      
    $args = array();
    $args["id_calendario_attivitacal"] = $id_calendario_attivitacal;
    $args["id_quote"] = $id_quote;
    $args["id_invoice"] = $id_invoice;
    

    $ret = $this->__call("calendari_attivitacal_prenotaAppuntamento", $args);
  
    return $ret;
  
  }

  public function InfoAppuntamento($id_calendario_attivitacal){          
    $args = array();
    $args["id"] = $id_calendario_attivitacal;   
    $ret = $this->__call("calendari_attivitacal_dettaglio", $args);
    
    return $ret;
  }

  public function annullaAppuntamento($id_calendario_attivitacal){          
    $args = array();
    $args["id_calendario_attivitacal"] = $id_calendario_attivitacal;   
    $ret = $this->__call("calendari_attivitacal_annullaAppuntamento", $args);
    
    return $ret;
  }
  


  

  public function modificaAttivitaCal($id_calendario_attivita,$data){
    
      
    $args = array();
    $args["id"] = $id_calendario_attivita;
    $args["params"] = $data;

    $ret = $this->__call("calendari_attivitacal_modifica", $args);
    
    return $ret;
  
  }


  public function __call($method, $args=array()){

    $form_params = array("method"=>$method,
              "request"=>array(
                  "token_key"=>array("token"=>get_option('WD_WINDDOC_TOKEN'),"token_app"=>$this->token_app),
                  )
            );
    foreach($args as $k=>$v){
      $form_params["request"][$k]=$v;
    }
  
    $response = wp_remote_post($this->url, array(
      'method' => 'POST',
      'headers' => "Accept: application/json",
      'httpversion' => '1.0',
      'sslverify' => false,
      'body' => http_build_query($form_params))
    );
  

    return json_decode($response["body"],true);

  }



}
