function showHotelModal(key) {
	
	$('body').modalmanager('loading');

	var url_general = '/Hotels/' + key + '?output_type=partial';
	if ($("#arrival_date").length) {
		var url_rates = '/Hotels/' + key + '/Rates?output_type=partial&' + $('#search_hotels').serialize();
	} else {
		var url_rates = '/Hotels/' + key + '/Rate-Outlook?output_type=partial';
	}

	$('#modal .modal-body').load(url_general, '', function() {	

		$('#modal').modal({
			modalOverflow: true,
			width: '840px',
			replace: false
		});

		$('#Room-Rates').load(url_rates, '', function() {
			$('#Room-Rates').fadeIn("slow");	
		});
	});	
}

function indexMapInit() {
	setTimeout(delayedMapInit, 2000);
}

function delayedMapInit() {
	
		var lat = query.lat;
		var lon = query.lon;

		mapCenter = new google.maps.LatLng(lat, lon);
		map = new google.maps.Map(document.getElementById('large_map'), {
		  'zoom': 11,
		  'center': mapCenter,
		  'mapTypeId': google.maps.MapTypeId.ROADMAP
		});	

		var infowindow = new google.maps.InfoWindow({
			maxWidth: 272
		});

		$.each(hotel_data, function() {
			
			var marker = new google.maps.Marker({
				map: map,
				position: new google.maps.LatLng(this.latitude, this.longitude),
				title: this.name,
				icon: 'https://props.io/static/img/hotel_0star.png'

			});
		
			marker.set('key', this.key);
			google.maps.event.addListener(marker, 'click', function() {
				showHotelModal(marker.get('key'));
			});

			google.maps.event.addListener(marker, 'mouseover', function() {
				var tile_ref = '#' + marker.get('key');
				var content = $(tile_ref).html();			
				infowindow.setContent(content);
				infowindow.open(map, marker);
				infowindow.set('key', marker.get('key'));
			});
		});

		var marker = new google.maps.Marker({
		  map: map,
		  position: new google.maps.LatLng(lat, lon),
		  title: 'Search center.'
		});		
}

function searchMapInit() {

	var lat = $("#lat").val();
	var lon = $("#lon").val();
	var proximity = $("#proximity").val();

	mapCenter = new google.maps.LatLng(lat, lon);
	map = new google.maps.Map(document.getElementById('map'), {
	  'zoom': 11,
	  'center': mapCenter,
	  'mapTypeId': google.maps.MapTypeId.ROADMAP
	});
			
	var marker = new google.maps.Marker({
	  map: map,
	  position: new google.maps.LatLng(lat, lon),
	  draggable: true,
	  title: 'Drag me to reposition your search.'
	});

	// Add a Circle overlay to the map.
	circle = new google.maps.Circle({
		strokeColor: "#000000",
		strokeOpacity: 0.9,
		strokeWeight: 1,
		fillColor: "#000000",
		fillOpacity: 0.25,
		map: map,
		radius: proximity * 1609.34 // In meters			  
	});

	circle.bindTo('center', marker, 'position');

	google.maps.event.addListener(marker, 'dragend', function (event) {
		$("#lat").val(this.getPosition().lat());
		$("#lon").val(this.getPosition().lng());
	});

	$("#modal").on({
		change: function(e) {
			var radius_meters = $(this).val() * 1609.34;
			circle.setRadius(radius_meters);
		}
	}, "#proximity");

	$("#modal").on({
		submit: function(e) {			
			var url = $(this).attr('action');
			var form_data = $(this).serialize();
			var tar_link = 'a[href="' + url + '"]';	
			var search_name = $("#update_search input[name=search_name]").val();

			$("#modal").modal('hide');

			$.ajax({
				url: url,
				type: 'PUT',
				data: form_data,
				success  : function(data){
					$(tar_link).html(search_name);
				}
			});

			e.preventDefault();
		}
	}, "#update_search");

	$("#modal").on({
		click: function(e) {
			
			var url = $(this).attr('href');

			if (confirm('Are you sure you want to archive this search?')) {
				$("#modal").modal('hide');
				$.ajax({
					url: url,
					type: 'DELETE',
					success  : function(data){
						tar_link = 'a[href="' + url + '"]';
						$(tar_link).parent().remove();
					}
				});
			}
			e.preventDefault();
		}
	}, "#archive_search");
}

$(document).ready( function() {	

	$default_modal = $('#modal');
	$default_modal_body = $('#modal div.modal-body');
	$hotel_modal = $('#hotel_modal');

	var offset_position = 0;
	var offsets = new Array();

	/*
	$('#modal').on({
		click: function (e) {
			e.preventDefault();
			$(this).tab('show');
		}
    }, "#hotel_tabs a");
	*/

	$("#open_map").on({
		click: function(e) {
			
			url = '/Map';
			$('#map_modal .modal-body').load(url, '', function() {
				$('#map_modal').modal({
					width: $(window).width() - 40,
					height: $(window).height() - 80
				});	
			});	

		    $('#myModal').on('hidden', function () {
    // do something…
    })

			e.preventDefault();
		}
	});

	$("#center_nav").on({
		submit: function(e) {
			
			var url = $(this).attr('action');
			var form_data = $(this).serialize();
			
			$.ajax({
				url: url,
				type: 'POST',
				data: form_data,
				dataType: 'json',
				success  : function(data){
					$('body').modalmanager('loading');

					url = url + '/' + data.search.key + '?output_type=partial'
					$default_modal_body.load(url, '', function(){
						$default_modal.modal({
							modalOverflow: true,
							width: '840px'
						});
					});

					$("#search_name").val("");
					$("#search_manager ul").append('<li><a href="/Searches/' + data.search.key + '" class="searches">' + data.search.name + '</a></li>');
				}
			});
			
			e.preventDefault();
		}
	}, "#create_search form");

	$("#right_nav").on({
		submit: function(e) {
			
			var url = $(this).attr('action');
			var form_data = $(this).serialize();
			
			$.ajax({
				url: url,
				type: 'POST',
				data: form_data,
				dataType: 'json',
				success  : function(data){
					$('body').modalmanager('loading');

					url = url + '/' + data.list.key + '?output_type=partial'
					$default_modal_body.load(url, '', function(){
						$default_modal.modal({
							modalOverflow: true,
							width: '840px'
						});
					});

					$("#list_name").val("");
					$("#list_manager .divider").before('<li><a href="/Lists/' + data.list.key + '" class="lists">' + data.list.name + '</a></li>');
				}
			});
			
			e.preventDefault();
		}
	}, "#list_manager form");

	$("#modal").on({
		click: function(e) {
			
			$(".search_details").slideUp("slow");
			var ol = $(this).parent().parent().find('ol');			
			ol.slideToggle('slow');
			e.preventDefault();
		}
	}, ".search_details_toggle");

	$("#modal").on({
		change: function(e) {
			
			var url = $(this).closest('form').attr('action');
			var form_data = 'search_key=' + $(this).val();
			
			if ($(this).prop('checked')==true){ 
				var type = 'POST';
			} else {
				var type = 'DELETE';
			}

			$.ajax({
				url: url,
				type: type,
				data: form_data,
				success  : function(data){
					//alert(data);
					// Send out status update
				}
			});
		}
	}, "#Included-Searches form :input");

	$("#modal").on({
		submit: function(e) {			
			var url = $(this).attr('action');
			var form_data = $(this).serialize();
			var tar_link = 'a[href="' + url + '"]';	
			var list_name = $("#update_list input[name=list_name]").val();

			$("#modal").modal('hide');

			$.ajax({
				url: url,
				type: 'PUT',
				data: form_data,
				success  : function(data){
					$(tar_link).html(list_name);
				}
			});

			e.preventDefault();
		}
	}, "#update_list");

	$("#modal").on({
		click: function(e) {
			
			var url = $(this).attr('href');

			if (confirm('Are you sure you want to archive this list?')) {
				$("#modal").modal('hide');
				$.ajax({
					url: url,
					type: 'DELETE',
					success  : function(data){
						tar_link = 'a[href="' + url + '"]';
						$(tar_link).parent().remove();
					}
				});
			}
			e.preventDefault();
		}
	}, "#archive_list");

	$("#modal").on({
		click: function(e) {
			if (offset_position > 0) {
				offset_position--;
			}
			$('.hscroll').scrollLeft(offsets[offset_position]);
			e.stopPropagation();
		}
	}, "#left_click");

	$("#modal").on({
		click: function(e) {
			if (offset_position < offsets.length) {
				offset_position++;
			}
			$('.hscroll').scrollLeft(offsets[offset_position]);
			e.stopPropagation();
		}
	}, "#right_click");

	$("#right_nav").on({
		click: function(e) {
			
			var href = $(this).attr('href');

			if (href != '/') {
				$('body').modalmanager('loading');

				url = href + '?output_type=partial'
				$default_modal_body.load(url, '', function(){
					$default_modal.modal({
						modalOverflow: true,
						width: '840px'
					});
				});

				e.preventDefault();
			}
		}
	}, "ul a");

	$("#content").on({
		click: function(e) {

			var key = $(this).attr('id');			
			showHotelModal(key);
			e.preventDefault();					
		}
	}, ".tile");
	
	
	$('#modal').on('shown', function () {		
		$('#modal').imagesLoaded( function(){
			offset_position = 0;
			offsets = new Array();

			$('#hotel_images img').each(
				function(){
					left = $(this).position().left;
					offsets.push(left);
			});
		});
    })

	$("#center_nav").on({
		click: function(e) {
			var href = $(this).attr('href');

			if (href == '/Sign/Up') {
				e.preventDefault();
				$("#sign_up_sign_in").slideUp("slow", function() {
					$("#sign_up").slideDown("slow");
				});
			}

			if (href == '/Sign/In') {
				e.preventDefault();
				$("#sign_up_sign_in").slideUp("slow", function() {
					$("#sign_in").slideDown("slow");
				});				
			}

			if (href == '/About/Learn-More') {
				e.preventDefault();
				$('body').modalmanager('loading');

				url = href + '?output_type=partial'
				$default_modal_body.load(url, '', function(){
					$default_modal.modal({
						modalOverflow: true,
						width: '893px'
					});
				});
			}			
		}
	}, "a");

	$("#center_nav").on({
		click: function(e) {

			$("#sign_up").slideUp("slow", function() {
				$("#sign_up_sign_in").slideDown("slow");
			});				
			
			e.preventDefault();
		}
	}, ".sign_up_cancel");

	$("#center_nav").on({
		click: function(e) {

			$("#sign_in").slideUp("slow", function() {
				$("#sign_up_sign_in").slideDown("slow");
			});				
			
			e.preventDefault();
		}
	}, ".sign_in_cancel" );

	$("#center_nav").on({
		submit: function(e) {
			e.preventDefault();

			var error = $(this).find('.alert');
			error.slideUp("slow");

			var ul = $(this).find('ul');
			ul.html('');

			$.post($(this).attr('action'), $(this).serialize(), function(data) {
				if (data.status == 'failure' ) {					
					$.each(data.errors, function(k, v) {
						ul.append('<li>' + v + '</li>');
					});
					error.slideDown("slow");
				} else {

					window.location.href = "/";
				}
			}, 'json');			
		}
	}, "#sign_in form, #sign_up form" );
	
	$("#content").on({
		click: function(e) {
			e.stopPropagation();

			if (confirm('Are you sure you want to archive this list')) {
				$.ajax({
					url: '/List'  + window.location.pathname,
					type: 'DELETE',
					success  : function(data){
						window.location.href = "/";
					}
				});
			}
		}
	}, "#archive_list");

	$("#content").on({
		click: function(e) {
			e.stopPropagation();
					
			$('#list_info').load('/List'  + window.location.pathname).fadeIn('slow');
		}
	}, "#edit_list");

	$("#content").on ({
		submit: function(e) {
			e.preventDefault();
			$.post($(this).attr('action'), $(this).serialize());
		}
	}, "#list_edit_form");

	var csrf = $('#csrf').val();


	$("#hotel_details").on({
		click: function(e) {		
			
			$('.hotel_dates_chooser').fadeIn("slow");	
			e.stopPropagation();
		}
	}, "#hdd");

	$("#hotel_details").on({
		change: function(e) {			

			if ($(this).val() != '0') {

				var tar = $(this).attr('id');

				if (tar == 'adults2') { $('.room3').fadeIn("slow");}
				if (tar == 'adults3') { $('.room4').fadeIn("slow");}
				if (tar == 'adults4') { $('.room5').fadeIn("slow");}
				if (tar == 'adults5') { $('.room6').fadeIn("slow");}
				if (tar == 'adults6') { $('.room7').fadeIn("slow");}
				if (tar == 'adults7') { $('.room8').fadeIn("slow");}
				
			}
			
			e.stopPropagation();
		}
	}, "#adults2, #adults3, #adults4, #adults5, #adults6, #adults7");

	$("#main").on({
		click: function(e) {
			e.stopPropagation();
		}
	}, ".list_title");
	

	$("#main").on({
		click: function(e) {
				
			$.post("/?ajax=true", { action: 'list_add', list_key: $(this).attr('id'), property_key: $('#property_key').val(), csrf: csrf }, listManager, "json");
			$('#list_menu').hide();
			e.preventDefault();
		}
	}, ".list_add");	

	$("#main").on({
		submit: function(e) {
			if ($('#list_title').val().length > 1) {
				f_data = $(this).serialize() + '&csrf=' + csrf;
				$.post("/?ajax=true", f_data, listManager, "json");
				$('#list_menu').hide();
				$('#list_title').val('');
			}			
			e.preventDefault();			
		}
	}, ".list_create");	

	$('#hotel_details').on('submit', '.hotel_dates_form', function(e){
		
		e.preventDefault();

		$('.hotel_dates, .hotel_rates').remove().fadeOut("slow");
		$('.loading').fadeIn("slow");

		$.ajax({
			type     : "GET",
			dataType : "html",
			url      : $(this).attr('action') + '?' + $(this).serialize() + '&ajax=true',
			success  : function(data){	
				$('.loading').fadeOut("slow", function(){
					$(data).hide().appendTo('.modal-body').fadeIn("slow", function() {
						
						$('#arrival_date')
							.datepicker()
							.on('changeDate', function(ev){
								//if (ev.date.valueOf() > departure_date.valueOf()){
								//	alert('Your arrival date must be before your departure date.');
								//} else {
								//	arrival_date = new Date(ev.date);
								//}
								$('#arrival_date').datepicker('hide');
							});
						$('#arrival_date').datepicker().on('show', function(e) {
							$('#departure_date').datepicker('hide');
						})
						$('#departure_date')
							.datepicker()
							.on('changeDate', function(ev){
								//if (ev.date.valueOf() < arrival_date.valueOf()){
								//	alert('Your departure date must be after your arrival date.');
								//} else {
								//	departure_date = new Date(ev.date);
								//}
								$('#departure_date').datepicker('hide');
							});
						$('#departure_date').datepicker().on('show', function(e) {
							$('#arrival_date').datepicker('hide');
						})
					});					
				});
			},
			error    : function(){
				console.log("ajax failed");
			}
		});		
	});		


	$('body')
		.off('click.dropdown touchstart.dropdown.data-api', '.dropdown')
		.on('click.dropdown touchstart.dropdown.data-api' , '.dropdown form', function (e) { e.stopPropagation() }
	);

	var $container = $('#main');
 
	//$container.imagesLoaded( function(){
	$(window).load(function(){

		$container.isotope({
			// options
			itemSelector : '.tile',
			layoutMode : 'masonry'
		});

		

	$("#main").on({
		mouseenter: function(e) {
			e.stopPropagation();
		}
	}, "#list_menu");

	$("#main").on({
		click: function(e) {

			$('#list_menu').css({
				position:'absolute',
				top: 28,
				left: -2,
				zIndex:5000
			});

			$('#list_menu').show('slow');

			e.preventDefault();			
		}
	}, ".list_menu_button");

	});

	/*
	$container.infinitescroll({
		
		navSelector: 'div#page_nav',
		nextSelector: 'div#page_nav a:first',
		itemSelector: '#main div.tile',
		loading: {
          finishedMsg: 'No more pages to load.',
          img: 'http://i.imgur.com/6RMhx.gif'
		}
	},
	function( newElements ) {

		var $newElems = $( newElements ).css({ opacity: 0 });
		$newElems.imagesLoaded( function() {
			$newElems.animate({ opacity: 1 });
			$container.isotope( 'appended', $newElems);
		});
	});
	*/

	$(document).on("inview", ".tile", function(e) {
		var $this = $(this);
		if(!$this.hasClass('loaded')) {
			$this.addClass('loaded');
			$this.css('visibility','visible').hide().fadeIn('slow');
		}
	});
});

(function ($) {
  $.Isotope.prototype._getCenteredMasonryColumns = function() {
    this.width = this.element.width();
   
    var parentWidth = this.element.parent().width();
   
                  // i.e. options.masonry && options.masonry.columnWidth
    var colW = this.options.masonry && this.options.masonry.columnWidth ||
                  // or use the size of the first item
                  this.$filteredAtoms.outerWidth(true) ||
                  // if there's no items, use size of container
                  parentWidth;
   
    var cols = Math.floor( parentWidth / colW );
    cols = Math.max( cols, 1 );

    // i.e. this.masonry.cols = ....
    this.masonry.cols = cols;
    // i.e. this.masonry.columnWidth = ...
    this.masonry.columnWidth = colW;
  };
 
  $.Isotope.prototype._masonryReset = function() {
    // layout-specific props
    this.masonry = {};
    // FIXME shouldn't have to call this again
    this._getCenteredMasonryColumns();
    var i = this.masonry.cols;
    this.masonry.colYs = [];
    while (i--) {
      this.masonry.colYs.push( 0 );
    }
  };

  $.Isotope.prototype._masonryResizeChanged = function() {
    var prevColCount = this.masonry.cols;
    // get updated colCount
    this._getCenteredMasonryColumns();
    return ( this.masonry.cols !== prevColCount );
  };
 
  $.Isotope.prototype._masonryGetContainerSize = function() {
    var unusedCols = 0,
        i = this.masonry.cols;
    // count unused columns
    while ( --i ) {
      if ( this.masonry.colYs[i] !== 0 ) {
        break;
      }
      unusedCols++;
    }
   
    return {
          height : Math.max.apply( Math, this.masonry.colYs ),
          // fit container to columns that have been used;
          width : (this.masonry.cols - unusedCols) * this.masonry.columnWidth
        };
  };
 
})(jQuery);