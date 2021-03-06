/**
 * Load total price
 */
function loadTotalPrice() { 
    var price = ($('#js-price-room').text()).split(':')[1];
    var quantity = ($('select[name=quantity').val());
    var duration = ($('select[name=duration').val());
    $('#js-price').text('$' + price);
    $('#js-quantity').text('x' + quantity);
    if ($('#js-language').text().trim() == 'English') {
        $('#js-duration').text(duration + ((duration == 1) ? ' night' : ' nights'));
    }
    else {
        $('#js-duration').text(duration + ' đêm');
    }
    $('#js-price-total').text('$ ' + price*quantity*duration);
}
$(document).ready(function() {
    $('#submit-payment-online').bind('click',function(e){
        e.preventDefault();
        var form = $(this.form);
        var title = $(this).attr('data-title');
        var body = '<i>' + $(this).attr('data-confirm') + '</i>';
        $('#title-content').html(title);
        $('#body-content').html(body);
        $('#confirm-payment').modal('show');
        $('#continue-payment-online-btn').one('click', function(){
            $('#booking-form').attr("action", $('#booking-form').attr("action")+ "?payment=online");
            
            $('#booking-form').submit();
            $('#confirm-payment').modal('hide');
        })
    });

    $('#submit').on('click',function(){
        $('#booking-form').submit();
    });
    $('#submit').on('click',function(){
        $('#booking-form').submit();
    });
    loadTotalPrice();

    $('#booking-form').change(function(){
        loadTotalPrice();
    });
    $('#request').keyup(function(){
        var request = ($('#request').val());
        if (request.length > 0) {
            $('#js-note-request').show();
        } else {
            $('#js-note-request').hide(); 
        }
    });
    ($('#msg-booking').text()) ? $('#booking-modal').modal('show') : $('#booking-modal').modal('hide');
});
