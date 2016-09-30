<?php 
/**
 * FritzBoxMonitor
 *
 * @author Sebastian Krebs <sebastian.krebs@snfachpresse.de>
 * 
 * This simple php Script shows the remaining data-volume in a pie chart overview. 
 * The Data is taken directly from the FritzBox via UPnP, which must be enabled. 
 *
 * @param string $fritzbox_adress -> 'fritz.box' or IP of Fritz Box
 * @param string $dataformat -> 'igdupnp' or 'upnp' depending on Version of Fritz Box
 * @param int $max_volume -> Datavolume in GB, default 1000 (=1 TB), might be 1024?
 * @param int $correction -> correction factor to adjust values, default: 0.81
 */

$fritzbox_adress = 'fritz.box';
$dataformat = 'igdupnp';
$max_volume = 1000;
$correction = 0.81;

$client = new SoapClient( 
	null, 
	array( 
		'location'   => 'http://'.$fritzbox_adress.':49000/'.$dataformat.'/control/WANCommonIFC1', 
		'uri'        => 'urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1', 
		'noroot'     => True, 
		'trace'      => True, 
		'exceptions' => 0 
	) 
); 

$action = 'GetTotalBytesSent'; 
$result = $client->{$action}(); 
if(is_soap_fault($result)) { 
	print(" Error: $result->faultcode | $result->faultstring"); 
} else { $sent = "{$result}\n"; } 

$action = 'GetTotalBytesReceived'; 
$result = $client->{$action}(); 
if(is_soap_fault($result)) { 
	print(" Error: $result->faultcode | $result->faultstring"); 
} else { $received = "{$result}\n"; } 

$data = array();
$data['sent'] = $sent*$correction;
$data['received'] = $received*$correction;
$data['total'] = $data['sent']+$data['received'];
$data['volume'] = $max_volume*pow(1024,3);
$data['remaining'] = $data['volume']-$data['total'];
if($data['remaining']<0) {$data['remaining'] = 0;}
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8" />
	<title>Data volume overview</title>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.3.0/Chart.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<link href="https://fonts.googleapis.com/css?family=Roboto:400" rel="stylesheet">
	<style>
		body {
			margin: 0px 0px 0px 0px;
			padding: 0px 0px 0px 0px;
			font-family: 'Roboto', sans-serif;
			background-color: #333;
			text-align: center;
			overflow: hidden;
		}
		canvas {
			background-color: #333;
			padding: 0;
			margin: auto;
			display: block;
			position: absolute;
			top: 30%;
			bottom: 30%;
			left: 0;
			right: 0;
		}
		#infos {
			position: absolute;
			bottom: 30px;
			width: 100%;
			color: #444;
			cursor: pointer;
			font-size: 1em;
    		font-size: 2vw;
		}
		#infos:hover {
			color: #fff;
		}
	</style>
</head>
<body>

	<canvas id="chart" width="500" height="300"></canvas>
	<script>
		function formatBytes(bytes,decimals) {
			if(bytes == 0) return '0 Byte';
			var k = 1024; // or 1024 for binary
			var dm = decimals + 1 || 3;
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
			var i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
		}

		var ctx = document.getElementById('chart');
		Chart.defaults.global.defaultFontFamily = 'Roboto';
		Chart.defaults.global.animation.easing = 'easeOutBounce';

		Chart.pluginService.register({
		  beforeDraw: function(chart) {
			var width = chart.chart.width,
				height = chart.chart.height,
				ctx = chart.chart.ctx;

			ctx.restore();
			var fontSize = (height / 200).toFixed(2);
			ctx.font = fontSize + 'em Roboto';
			ctx.textBaseline = 'middle';

			var text1 = formatBytes(<?php echo $data['remaining'] ?>,0),
				text1X = Math.round((width - ctx.measureText(text1).width) / 2),
				text1Y = height / 2;
				ctx.fillStyle = 'white';
				ctx.fillText(text1, text1X, text1Y);
   
			var fontSize = (height / 300).toFixed(2);
			ctx.font = fontSize + 'em Roboto';
			ctx.textBaseline = 'middle';
			var text2 = 'remaining',
				text2X = Math.round((width - ctx.measureText(text2).width) / 2),
				text2Y = height / 2+(text1Y/6);
				ctx.fillStyle = '#6e6e6e';
				ctx.fillText(text2, text2X, text2Y);
	
			ctx.save();
			}
		});

		var myChart = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: [
					'sent',
					'received',
					'remaining'
				],
				datasets: [{
					data: [
						<?php echo $data['sent'] ?>,
						<?php echo $data['received'] ?>,
						<?php echo $data['remaining'] ?>
					],
					backgroundColor: [
						'rgba(181, 50, 48, 1)',
						'rgba(48, 138, 181, 1)',
						'rgba(60, 60, 60, 1)',
					],
					borderColor: [
						'rgba(255, 255, 255, 1)',
						'rgba(255, 255, 255, 1)',
						'rgba(255, 255, 255, 1)',
					],
					borderWidth: 0,
				}]
			},
			options: {
        		responsive: true,
				cutoutPercentage: 70,
				title: {
					display: false,
				},
				legend: {
					display: false,
				},
				tooltips: {
					enabled: true,
					bodyFontSize: 40,
					caretSize: 15,
					xPadding: 20,
					yPadding: 20,
		
					callbacks: {
						label: function(tooltipItem, data) {
							var allData = data.datasets[tooltipItem.datasetIndex].data;
							var tooltipLabel = data.labels[tooltipItem.index];
							var tooltipData = allData[tooltipItem.index];
							return tooltipLabel + ': ' + formatBytes(tooltipData,0);
						}
					}

				}
			}
		});
	</script>

	<div id='infos'></div>
	<script>
		output = 'sent: '+formatBytes(<?php echo $data['sent'] ?>,0)+' | ';
		output += "received: "+formatBytes(<?php echo $data['received'] ?>,0)+' | ';
		output += 'total: '+formatBytes(<?php echo $data['total'] ?>,0)+' | ';
		output += 'remaining: '+formatBytes(<?php echo $data['remaining'] ?>,0);
		document.getElementById('infos').innerHTML += output;
	</script>

</body>
</html>
