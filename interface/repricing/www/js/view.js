function RepricingView(patientID, mypcc){
    this.patientID = patientID;
    this.populateUser();
    this.wireEventListeners();
    this.mypcc = mypcc;
}

RepricingView.prototype.populateUser = function() {
    $.get('service/patients.php?patientID=' + this.patientID + '&ts=' + new Date().getTime(), null, function(data) {
        $("#j-patient-name")
            .html(data.name)
            .attr('data-patient-ID', data.id);
    });
};

RepricingView.prototype.recalculateBalances = function() {
    var totalBilled = parseFloat( $("#j-total-billed").val() || "0.0" ) ;

    var chargeEffect = 0;
    $.each( $('.j-claim-detail-entry-row:visible'), function(i, transaction) {
        var thisCharge = parseFloat( $(transaction).find('.j-service-charge').val() || "0.0" );
        var thisAllowedAmount = parseFloat( $(transaction).find('.j-service-allowed').val() || "0.0" );
        chargeEffect -= (thisCharge - thisAllowedAmount);
    });

    $("#j-remaining-balance-net-pay").val(totalBilled + chargeEffect);
};

RepricingView.prototype.buildClaimDetailEntry = function(serviceCode, claimEntryDescription) {
    var self = this;
    var newRow = $(".j-claim-detail-entry tr").clone();
    if ( serviceCode ) {
        newRow.find('.j-service-code').val(serviceCode);
    }
    if ( claimEntryDescription ) {
        newRow.find('.j-claim-entry-description').html(claimEntryDescription);
    }

    //
    // attach to DOM
    //
    $("#j-claim-detail-list table").append(newRow);

    //
    // setup calendar
    //
    var receivedDate = $('#j-received-date').val();
    var rowID = guid();
    newRow.find('.j-claim-detail-date')
        .val( receivedDate )
        .focus()
        .attr('id', rowID )
        .attr('name', rowID )
        .keyup( function() {
            datekeyup(this,mypcc);
        })
        .blur( function() {
            dateblur(this,mypcc);
        });

    newRow.find('.j-claim-detail-date-btn')
        .attr('id', rowID + '-btn' )
        .attr('name', rowID + '-btn');

    Calendar.setup({inputField:rowID, ifFormat:"%Y-%m-%d", button:rowID + '-btn'});

    //
    // setup service code
    //
    newRow.find(".j-service-code")
        .autocomplete({
            source: 'service/service_code.php',
            minLength: 2,
            select: function( event, ui ) {
                $(this).parent().parent().find('td.j-claim-entry-description').html( ui.item.label );

                // focus on the charge column once selected
                newRow.attr('data-service-code-ID', ui.item.id);
                newRow.find(".j-service-charge").focus();
                newRow.find('.j-service-allowed').val(ui.item.allowedCharge || 0.00);
            }
        });

    //
    // setup delete
    //
    newRow.find('.j-claim-entry-delete-btn')
        .click( function() {
            newRow.remove();
            if ( $('.j-claim-detail-entry-row:visible').length < 1 ) {
                $("#j-claim-detail-list").hide();
            }
            self.recalculateBalances();
        });

    //
    // setup service charge
    //
    newRow.find('.j-service-charge')
        .change( function() {
            self.recalculateBalances();
        });
};

RepricingView.prototype.saveProvider = function( firstName, middleName, lastName, npi ) {
    var deferred = Q.defer();

    $.ajax({
        type: "POST",
        url: 'service/providers_create.php',
        data: {
            fname: firstName,
            mname: middleName,
            lname: lastName,
            npi: npi
        },
        success: function(data, textStatus, jqXHR) {
            $("#j-provider").val(data.value);
            $("#j-provider").attr('data-provider-ID', data.id);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert(errorThrown);
        },
        dataType: 'json'
    });

    deferred.resolve();

    return deferred.promise;
};

RepricingView.prototype.wireEventListeners = function() {
    var self = this;

    $(document).ready( function() {
        $("#j-btn-add-service").click( function() {
            $("#j-claim-detail-list").show();

            self.buildClaimDetailEntry();
        });


        //
        // setup claim type
        //
        $.get('service/claim_types.php', null, function(data) {
            $.each( data, function(i, claimType) {
                $("#j-claim-type-selection").append("<option value='" + claimType.label + "'>" +claimType.label + " </option>");
            });
        });

        //
        // setup provider
        //
        $("#j-provider")
            .focus()
            .autocomplete({
            source: 'service/providers.php',
            minLength: 2,
            select: function( event, ui ) {
                $(this).attr('data-provider-ID', ui.item.id );
            }
        }).change( function() {
            $(this).attr('data-provider-ID', '' );
        }).focusout( function() {
            if (!$(this).attr('data-provider-ID') && $("#j-provider").val().trim() ) {
                $(this).val("");
            }
        });

        $("#j-btn-add-provider").click( function() {
            var type = "";
            dlgopen('../usergroup/addrbook_edit.php?isAuthorized=1&type=' + type, '_blank', 700, 550);
        });
        //});

        //
        // setup claim date and service date
        //
        Calendar.setup({inputField:"j-claim-date", ifFormat:"%Y-%m-%d", button:"j-claim-date-btn"});
        Calendar.setup({inputField:"j-received-date", ifFormat:"%Y-%m-%d", button:"j-received-date-btn"});

        //
        // setup total billed
        //
        $("#j-total-billed").change( function() {
            self.recalculateBalances();
        });

        //
        // setup received date
        //
        var today = new Date();
        $("#j-received-date").val( today.format("yyyy-mm-dd") );

        //
        // setup save claim
        //
        $("#j-btn-add-save-claim").click( function() {
           self.saveClaim();
        });
    });
};

RepricingView.prototype.saveClaim = function() {
    var self = this;
    // 1. scrape claim metadata from the UI
    var summary = {
        patientID : self.patientID,
        providerID : $("#j-provider").attr('data-provider-ID'),
        claimDate: $("#j-claim-date").val(),
        receivedDate: $("#j-received-date").val(),
        totalBilled: $("#j-total-billed").val(),
        claimType: $("#j-claim-type-selection").val()
    };

    var transactions = [];
    $.each( $('.j-claim-detail-entry-row:visible'), function(i, transaction) {
        var row = $(transaction);
        var thisTransactionDate = row.find('.j-claim-detail-date').val();
        var thisCharge = parseFloat( row.find('.j-service-charge').val() || "0.0" );
        var thisAllowedAmount = parseFloat( row.find('.j-service-allowed').val() || "0.0" );
        var serviceCodeID = row.attr('data-service-code-ID');
        transactions.push( {
            serviceCodeID : serviceCodeID,
            transactionDate : thisTransactionDate,
            charge : thisCharge,
            allowed : thisAllowedAmount
        });
    });

    var claim = {
        summary : summary,
        transactions : transactions
    };

    // 2. call the backend to save it
    console.log(JSON.stringify(claim, null, 1));
    $.ajax({
        type: "POST",
        contentType: "application/json; charset=utf-8",
        url: "service/claims.php",
        data:  JSON.stringify(claim),
        success: function (response) {
            // todo
        }
    });

};

// private
function guid() {
    function s4() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    }
    return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
        s4() + '-' + s4() + s4() + s4();
}
