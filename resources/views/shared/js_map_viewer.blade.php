                if ($(e.target).is('.loc-static')) {
                    var target = $(e.target);
                    var lat = target.data('lat');
                    var lon = target.data('lon');

                    var gs_src = 'https://maps.googleapis.com/maps/api/staticmap?center=' + lat +','+ lon + '&markers=color:red%7Clabel:P%7C' + lat +','+ lon + '&zoom=12&size=400x400&key={{env('GOOGLE_MAP_KEY')}}';

                    $('#map-view').attr('src', gs_src);

                    $('#map-view-modal').modal();

                }
