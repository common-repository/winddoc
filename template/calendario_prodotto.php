<?php
    wp_enqueue_script( 'winddoc_calendar.js', plugins_url( '/utility/winddoc_calendar.js', __FILE__ ));
    wp_enqueue_style( 'winddoc_calendar.css', plugins_url( '/utility/winddoc_calendar.css', __FILE__ ));
    wp_enqueue_style( 'jquery-ui.css', plugins_url( '/utility/jquery-ui.css', __FILE__ ));
    $ajax_nonce = wp_create_nonce( 'action_winddoc_appuntamenti' );

    //https://uicookies.com/bootstrap-datepicker/
    ?>
<br>


<?php  if(count($calendari)>1):?>
    <?php if($_wd_winddoc_calendar_label!="") : ?>
        <strong><?php echo esc_html( $_wd_winddoc_calendar_label); ?></strong>
    <?php endif; ?>
    <div>
    <ul id="lista_calentari">
    <?php foreach($calendari as $i=>$calendario):?>         
        <li><a class="<?php echo esc_html ($i==0 ? 'active' : '');?>" onclick="jQuery('#id_calendario_attivita').val('<?php echo esc_js( $calendario["id_calendario_attivita"]);?>');getDatePrenotabili();jQuery('#lista_calentari li a').removeClass('active');jQuery(this).addClass('active');"><?php echo esc_html($calendario["nome"]);?></a></li>
    <?php endforeach;?>
    </ul>
    <div>
<?php endif; ?>
<input type="hidden" id="id_calendario_attivita" name="id_calendario_attivita" value="<?php echo esc_attr($calendari[0]["id_calendario_attivita"]);?>">

<div id="winddoc-calendar">
    <div id="sandbox-container"></div>
    <div class="loading" style="display: none;"><div class="lds-dual-ring"></div></div>
</div>
<br>
<strong id="seleziona_orario" style="display: none;">Seleziona Orario</strong>
<strong id="orario_non_disponibile" style="display: none;">Nessun Appuntamento Disponibile</strong>
<div>
<ul id="lista_id_calendario_attivitacal"></ul>
<input type="hidden" id="id_calendario_attivitacal" name="id_calendario_attivitacal" value="">

<p class="price" id="price_booking"></p>
</div>
<script>
    var winddoc_ajax_admin = '<?php echo admin_url( 'admin-ajax.php' ) ?>';
    var winddoc_ajax_admin_secure = '<?php echo esc_js($ajax_nonce); ?>;'
</script>
<style>
    .quantity{
        display: none;
    }
</style>