var last_periodi = new Array();
var current_data_select = "";
var d = new Date();
current_data_select = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();





jQuery(document).ready(function(){
    jQuery('#sandbox-container').datepicker({
        language:'it',
        beforeShowDay: beforeShowDay,
        onChangeMonthYear: function (year,month) {
            current_data_select = year+"-"+month+"-01";
            getDatePrenotabili(current_data_select);
        },
        onSelect: function(value, date){
            
            getAppuntamentiByData(date.currentYear,date.currentMonth+1,date.currentDay);
        }
    }).on("changeDate", function(e) {
        
        /*var date = e.date; // Or the date you'd like converted.
        var isoDateTime = new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        
        getBookDay(isoDateTime);
        process_periodi();*/
    }).on("changeMonth", function(e) {
        
        //getBookMonth(e.date);
    }).datepicker('setDate', new Date());
   // getBookMonth(new Date());
   getDatePrenotabili(undefined,true);
   jQuery(".single_add_to_cart_button").prop("disabled",true);
   
});

function getDatePrenotabili(date,sel){
    if(date==undefined){        
        date = current_data_select;
    }
    if(sel==undefined){    
        sel = false;
    }
    jQuery('#winddoc-calendar .loading').show();
    var data = {
        action: 'frontend_action_winddoc_appuntamenti_get_book_month', // your action name 
        security: winddoc_ajax_admin_secure,
        data : date,
        id_calendario_attivita : jQuery("#id_calendario_attivita").val(),
    };
    jQuery("#lista_id_calendario_attivitacal").html("");
    jQuery('#id_calendario_attivitacal').val('');
    jQuery(".single_add_to_cart_button").prop("disabled",true);
    jQuery.ajax({
        url: winddoc_ajax_admin, // get ajaxurl
        type: 'POST',
        dataType: "json",
        data: data,
        success: function (response) {
            last_periodi = response;
            process_periodi();
            jQuery('#winddoc-calendar .loading').hide();
            if(sel){
                var dd = new Date(); 
                getAppuntamentiByData(dd.getFullYear(),dd.getMonth()+1,dd.getDate());
            }
        },
        error: function (response) {
            jQuery('#winddoc-calendar .loading').hide();
        }
    });

}

function beforeShowDay(date) {
    d2 = new Date();
    d2.setHours(0, 0, 0, 0);
    if(date.getTime()<=(d2.getTime()-1)){
        return [false,"","unAvailable"]; 
    }
    for(i=0; i< last_periodi.length; i++){    
        
        var t = last_periodi[i]["data_inizio"].split(" ")[0].split("-");
           
        
        if(t[0] == date.getFullYear()){
            if ((t[1]-1)==date.getMonth()){
                if (t[2]==date.getDate()){           
                    return [true, "","Available"];
                }
            }
        }
    }

    return [false,"","unAvailable"]; 
    
    
}

var listini;

function getAppuntamentiByData(anno,mese,giorno){
    if(giorno<10){giorno = "0"+giorno;}
    if(mese<10){mese = "0"+mese;}
    var periodi_ok = new Array();
    for(i=0; i< last_periodi.length; i++){
        data_inizio = last_periodi[i]["data_inizio"].split(" ");
        
        if(data_inizio[0]==anno+"-"+mese+"-"+giorno){
            periodi_ok.push(last_periodi[i]);
        }
    }
    
    var html = "";
    
    for(i=0; i< periodi_ok.length; i++){        
        data_inizio = periodi_ok[i]["data_inizio"].split(" ");
        var orario = data_inizio[1].split(":");
        html = html + '<li><a class="'+(i==0 ? 'active' : '')+'" onclick="jQuery(\'#id_calendario_attivitacal\').val(\''+periodi_ok[i]["id_calendario_attivitacal"]+'\');jQuery(\'#lista_id_calendario_attivitacal li a\').removeClass(\'active\');jQuery(this).addClass(\'active\');cambiaDataOra();">'+data_inizio[0]+" Ore "+orario[0]+':'+orario[1]+'</a></li>';
    }
    
    jQuery("#lista_id_calendario_attivitacal").html(html);
    if(html==""){
        jQuery(".single_add_to_cart_button").prop("disabled",true);
    }else{
        jQuery(".single_add_to_cart_button").prop("disabled",false);
    }
    
    if(periodi_ok.length>0){
        jQuery('#id_calendario_attivitacal').val(periodi_ok[0]["id_calendario_attivitacal"]);                
    }
    cambiaDataOra();
}

function cambiaDataOra(){
    var id_calendario_attivitacal = jQuery('#id_calendario_attivitacal').val();
    if(last_periodi.length>0){
        jQuery("#seleziona_orario").show();
        jQuery("#orario_non_disponibile").hide();
        jQuery("#id_calendario_attivitacal").show();

        for(i=0; i< last_periodi.length; i++){
            
            
            if(last_periodi[i]["id_calendario_attivitacal"]==id_calendario_attivitacal){
                jQuery("#hidden_id_calendario_attivitacal").val(id_calendario_attivitacal);
                data_inizio = last_periodi[i]["data_inizio"].split(" ");
                var orario = data_inizio[1].split(":");
                
                jQuery("#hidden_id_calendario_attivitacal_data").val(data_inizio[0]+" "+orario[0]+':'+orario[1]);
            }
        }
    }else{
        jQuery("#seleziona_orario").hide();
        jQuery("#orario_non_disponibile").show();
        jQuery("#id_calendario_attivitacal").hide();
    }
    
}

function process_periodi(){
    jQuery('#sandbox-container').datepicker("refresh");
}



