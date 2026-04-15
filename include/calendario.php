<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>



<script type="text/javascript">

	jQuery(document).ready(function($) {
		$.datepicker.regional['es'] = {
			closeText: 'Cerrar',
			prevText: 'Ant',
			nextText: 'Sig',
			currentText: 'Hoy',
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio', 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
			dayNames: ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'],
			dayNamesShort: ['Dom','Lun','Mar','Mier','Juv','Vie','Sab'],
			dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sab'],
			weekHeader: 'Sm',
			dateFormat: 'dd/mm/yy',
			firstDay: 1,
			isRTL: false,
			showMonthAfterYear: false,
			yearSuffix: ''};
		$.datepicker.setDefaults($.datepicker.regional['es']);

		/**
		 * Genera 200 Datepicker [#datepicker1]
		 */
		for(x=1; x < 200; x++){
		   $("#datepicker"+x).datepicker({
		   	changeMonth: true,
		   	changeYear: true,
		   	yearRange:'-150:+150'
		   });
		}
	});
</script>



