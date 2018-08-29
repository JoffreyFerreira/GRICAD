function genGraph(tab, minY, titleY, titleX) {

	var data_list = [];

	for (id in tab) {
		data_list.push({
			toolTipContent : "id : "+id+", y: {y} ",
			type: "line",
			name: String(id),
			indexLabel: "{y}",
			yValueFormatString: "#0.##",
			showInLegend: true,
			dataPoints: tab[id]
		});
	}

	var chart = {
		animationEnabled: true,
		axisY:{
			minimum: minY,
			title: titleY,
		},
		axisX:{
			title: titleX,
		},
		theme: "light2",
		legend:{
			cursor: "pointer",
			verticalAlign: "center",
			horizontalAlign: "right",
		},
		data : data_list
	};

	return(chart);

}