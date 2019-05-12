$(document).ready(function () {
    $('.location-search').selectize({
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
     * Disabling all non active properties
     */
    $('.properties-table').each(function (index, element) {
        var propTable = $(element);
        if (!propTable.hasClass('active')) {
            propTable.find(':input').prop('disabled', true);
        }
    });

    /**
     * Show property table and enable it properties on person type change
     */
    $('.person-type-selector input').change(function () {
        var currentPropTable = $('.properties-table.active');
        currentPropTable.find(':input').prop('disabled', true);
        currentPropTable.removeClass('active');

        var personTypeId = $(this).val();
        var newPropTable = $('.properties-table.properties-' + personTypeId);
        newPropTable.find(':input').prop('disabled', false);
        newPropTable.addClass('active');
    });
});
