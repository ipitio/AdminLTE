
var speedlabels = [],downloadspeed=[], uploadspeed=[] ,speeddata = [], serverPing =[];

function updateSpeedTestData()
{

	var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "June",
		  "July", "Aug", "Sept", "Oct", "Nov", "Dec"
		];

	function formatDate(itemdate)
	{
		var testtime = new Date(itemdate);
		formattedtime = ("0" + testtime.getHours()).slice(-2)   + ":" +
    ("0" + testtime.getMinutes()).slice(-2);
		return formattedtime;
	}

	var jsonData = $.ajax({
		url: 'api.php?getSpeedData24hrs&PHP',
		dataType: 'json',
	}).done(function (results) {

			results.forEach(function(packet) {
			if(speedlabels.indexOf(formatDate(packet.start_time)) === -1 )
				{
					speedlabels.push(formatDate(packet.start_time));
					uploadspeed.push(parseFloat(packet.upload));
					downloadspeed.push(parseFloat(packet.download));
					serverPing.push(parseFloat(packet.server_ping));

				}
			  speedChart.update();
			});
			speeddata = results;
	});
}


setInterval(function(){
	// console.log('updateSpeedTestData');
  updateSpeedTestData();
}, 6000);


var speedChartctx = document.getElementById("speedtestChart");
var speedChart = new Chart(speedChartctx, {
	type: 'line',
	data:
    {
  		labels: speedlabels,
  		datasets: [{
  			label: 'Download Mbps',
  			data: downloadspeed,
  			backgroundColor: 'rgba(75, 192, 192, 0.1)',
  			borderColor:'rgba(75, 192, 192, 1)',
  			borderWidth: 1,
  			cubicInterpolationMode: 'monotone',
        yAxisID: "y-axis-1",
  		},
  		{
  			label: 'Upload Mbps',
  			data: uploadspeed,
  			backgroundColor: 'rgba(255, 99, 132, 0.1)',
  			borderColor: 'rgba(255,99,132,1)',
  			borderWidth: 1,
        yAxisID: "y-axis-1",
  		},
      {
  			label: 'Ping ms',
  			data: serverPing,
  			backgroundColor: 'rgba(69,237,33,0.0)',
  			borderColor: 'rgba(69,237,33,1)',
  			borderWidth: 1,
        borderDash: [5, 5],
        yAxisID: "y-axis-2",
  		}

		]
	},
	options: {
		 responsive: true,
       		maintainAspectRatio: false,
		legend : {
				display : false
			},
		scales: {
			yAxes: [{
                  type: "linear", // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
                  display: true,
                  position: "left",
                  id: "y-axis-1",
              },
              {
                  type: "linear", // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
                  display: true,
                  position: "right",
                  id: "y-axis-2",
              }
            ],
      xAxes: [
              {
                  display: true,
                  scaleLabel: {
                      display: false
                  }
              }
            ]
		       },
    tooltips: {
      enabled : true,
        mode: "x-axis",
        intersect: false,
    }
	}
});
updateSpeedTestData();