	var holidaybutton = document.getElementById("holidaybutton");
	var holidayclass = document.getElementsByClassName("holidaytd");
	var daysselected = [];
	// var holidayclass = document.querySelectorAll(".holiday");
	$("#submitleavebtn").on("click",function(e){
			//alert(JSON.stringify(daysselected));
			var dataobj= $('#dataobj');
			var bid_round_id = dataobj.data("bidroundid");
			var bid_line_id = dataobj.data("bidlineid");
			var bid_group_id = dataobj.data("bidgroupid")
			$.ajax({
				method: "POST",
				url: "./displayschedulesubmit.php",
				data: {
					mydata: daysselected,
					action: "submitprimeleave",
					bid_round_id: bid_round_id,
					bid_line_id: bid_line_id,
					bid_group_id: bid_group_id
				},
				success: function(s){
					// location.reload();
				}
			})
			
			;
				
	});
	function holidayToggle(){
		for(var i = holidayclass.length-1;i>-1;i--){
			if(holidaybutton.innerText == "Show"){
				holidayclass[i].classList.remove("holidayhidden") ;
			} else holidayclass[i].classList.add("holidayhidden") ;
		}
		if(holidaybutton.innerText == "Show") holidaybutton.innerText = "Hide";
		else holidaybutton.innerText = "Show";
	}
		

	if(isuptobidleave){
		$('.scheduletable tr td').on('click',function(e){
			var functhis = $(this);

			if(!functhis.hasClass("RDO") && functhis.closest('td').children().hasClass("emptyslot")) {
			
			if(!functhis.hasClass("selectdate")){
				functhis.addClass("selectdate");
				var dataarray = functhis.html().split("<br>");
				var datadate = functhis.data("date");
				
				daysselected.push(datadate);
				daysselected.sort();
				var daysselecteddivtext = "";
				for(var i =0;i<(daysselected.length);i++ ){
					if(i != 0) daysselecteddivtext += "<br>";
					
					daysselecteddivtext += (i+1) + ". " + daysselected[i] + " <span class='w3-tiny'>" + returnDay(new Date(daysselected[i]).getDay()) + "</span>";
				}
				$('#daysselected').html(daysselecteddivtext);
				
				$('.daysselecteddiv').show();
				$('#submitleave').show();
			} else {
				functhis.removeClass("selectdate");
				
				var dataarray = functhis.html().split("<br>");
				var datadate = functhis.data("date");
				var daysselecteddivtext = "";
				var daysselectedlength = (daysselected.length);
				var removefromdaysselected;
				for(var i =0;i<daysselectedlength;i++ ){
					if(daysselected[i] == datadate) {
						removefromdaysselected = i;
						daysselected.splice(removefromdaysselected,1);
						if(i ==(daysselectedlength -1)) continue;
						daysselectedlength -= 1;
						
					}
					if(i != 0) daysselecteddivtext += "<br>";
					daysselecteddivtext += (i+1) + ". " + daysselected[i] + " <span class='w3-tiny'>" + returnDay(new Date(daysselected[i]).getDay()) + "</span>";
				}
				$('#daysselected').html(daysselecteddivtext);
				if (daysselected.length === 0)	{
					$('.daysselecteddiv').hide();
					$('#submitleave').hide();
					
				}
			}
		}
	});
}
function returnDay(daynum){
	switch(daynum){
		case 0:
			return "Mon";
		case 1:
			return "Tue";
		case 2:
			return "Wed";
		case 3:
			return "Thu";
		case 4:
			return "Fri";
		case 5:
			return "Sat";
		case 6:
			return "Sun";
		
	}
}