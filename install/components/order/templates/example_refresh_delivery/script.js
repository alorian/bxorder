$(document).ready(function () {
    var $locationSearch = $('.location-search');

    $locationSearch.selectize({
        valueField: 'code',
        labelField: 'label',
        searchField: 'label',
        create: false,
        render: {
            option: function (item, escape) {
                return '<div class="title">' + escape(item.label) + '</div>';
            }
        },
        load: function (q, callback) {
            if (!q.length) return callback();

            var query = {
                c: 'opensource:order',
                action: 'searchLocation',
                mode: 'ajax',
                q: q
            };

            $.ajax({
                url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
                type: 'GET',
                error: function () {
                    callback();
                },
                success: function (res) {
                    if (res.status === 'success') {
                        callback(res.data);
                    } else {
                        console.log(res.errors);
                        callback();
                    }
                }
            });
        }
    });

    /**
     * Recalculate all deliveries on location prop change
     */
    var $deliveryBlock = $('#os-order-delivery-block');

    function renderDelivery(deliveryData) {
        var html = '';
        html += '<label class="delivery-label delivery' + deliveryData.id + '">';
        html += '<input type="radio" name="delivery_id" class="delivery-input" value="' + deliveryData.id + '"> ';
        html += deliveryData.name;
        html += ', <span class="delivery-price">' + deliveryData.price_display + '</span>';
        html += '</label>';
        html += '<br>';

        $deliveryBlock.append(html);
    }

    $locationSearch.change(function (event) {
        var locationCode = $(event.target).val();

        if (locationCode.length > 0) {
            var formData = $('#os-order-form').serialize();

            var query = {
                c: 'opensource:order',
                action: 'calculateDeliveries',
                mode: 'ajax'
            };

            var request = $.ajax({
                url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
                type: 'POST',
                data: formData
            });

            request.done(function (result) {
                var selectedDelivery = $deliveryBlock.find('input:checked');
                var selectedDeliveryId = 0;
                if(selectedDelivery.length > 0) {
                    selectedDeliveryId = selectedDelivery.val();
                }

                $deliveryBlock.html('');
                $.each(result.data, function (i, deliveryData) {
                    renderDelivery(deliveryData);
                });

                if(selectedDeliveryId > 0) {
                    $deliveryBlock.find('label.delivery' + selectedDeliveryId + ' input').prop('checked', true);
                }
            });

            request.fail(function () {
                console.error('Can not get delivery data');
            });
        }
    });
});
