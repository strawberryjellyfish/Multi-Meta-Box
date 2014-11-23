/**
 * Multi Meta Box Geocoder using Google Place Autocomplete
 * Robert Miller <rob@strawberryjellyfish.com>
 * @since 3.2.3
 */
$(function(){
  $('div.mmb-geocoder').each( function() {
    var completer =  $(this).find('input.mmb-geocoder-completer');
    var mapCanvas = $(this).find('div.mmb-geocoder-map-canvas');
    var lat = $(this).find('input.mmb-geocoder-latitude-input').val();
    var lng = $(this).find('input.mmb-geocoder-longitude-input').val();
    var thisId = $(this).attr('id');
    completer.geocomplete({
      map: mapCanvas,
      details: '#' + thisId,
      detailsAttribute: "data-geo",
      location: Array(lat, lng),
      mapOptions: {
        streetViewControl: false,
        panControl: false,
        zoomControl: true,
      },
      markerOptions: {
        draggable: true
      },
      types: ["geocode"],
    });

    completer.bind("geocode:dragged", function(event, latLng){
      $(this).geocomplete("find", latLng.lat() + "," + latLng.lng());
      $(this).siblings("span.mmb-geocoder-reset").show();
    });
  });

  $(".mmb-geocoder-find-button").click(function(){
    $(this).prev('input.mmb-geocoder-completer').trigger("geocode");
  });
});