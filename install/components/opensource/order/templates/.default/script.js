jQuery(document).ready(function($) {
    var $orderForm = $('[name="os-order-form"]'),
        $location = $('.location-search', $orderForm);

    $orderForm.on('submit', function(e) {
        e.preventDefault();

        /**
         * Serialize form data with files
         *
         * @param  [jQueryObject] $form form for serialize.
         * @return [FormData]
         */
        var serializeForm = function ($form) {
            var formData = new FormData();

            // Append form data.
            var params = $form.serializeArray();
            $.each(params, function (i, val) {
                formData.append(val.name, val.value);
            });

            // Append files.
            $.each($form.find("input[type='file']"), function (i, tag) {
                $.each($(tag)[0].files, function (i, file) {
                    formData.append(tag.name, file);
                });
            });

            return formData;
        }

        var query = {
            c: 'opensource:order',
            action: 'saveOrder',
            mode: 'ajax'
        };

        $.ajax({
            url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
            type: 'POST',
            dataType: 'json',
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            data: serializeForm($orderForm),
            success: function (res) {
                console.log(res)
                alert('Check request results in console!');
            }
        });
    });

    $location.selectize({
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
});
