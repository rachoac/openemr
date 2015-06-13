function RepricingView(patientID){
    debugger;
    this.patientID = patientID;
    this.populateUser();
    this.wireEventListeners();
}

RepricingView.prototype.populateUser = function() {
    $.get('service/patients.php?patientID=' + this.patientID + '&ts=' + new Date().getTime(), null, function(data) {
        $("#j-patient-name").html(data.name).attr('data-patient-ID', data.id);
    });
};

RepricingView.prototype.buildClaimDetailEntry = function(serviceCode, claimEntryDescription) {
    var newRow = $(".j-claim-detail-entry tr").clone();
    if ( serviceCode ) {
        newRow.find('.j-service-code').val(serviceCode);
    }
    if ( claimEntryDescription ) {
        newRow.find('.j-claim-entry-description').html(claimEntryDescription);
    }

    // attach to DOM
    $("#j-claim-detail-list table").append(newRow);
    newRow.find(".j-service-code").focus();

    newRow.find(".j-service-code").autocomplete({
        source: 'service/service_code.php',
        minLength: 2,
        select: function( event, ui ) {
            $(this).parent().parent().find('td.j-claim-entry-description').html( ui.item.label );

            // focus on the charge column once selected
            newRow.find(".j-service-charge").focus();
        }
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
            $("#j-provider").attr('data-provider-id', data.id);
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

        $("#j-provider").autocomplete({
            source: 'service/providers.php',
            minLength: 2,
            select: function( event, ui ) {
                $(this).attr('data-provider-id', ui.item.id );
            }
        }).change( function() {
            $(this).attr('data-provider-id', '' );
        }).focusout( function() {
            if (!$(this).attr('data-provider-id')) {
                $(this).val("");
                $("#j-btn-add-provider").click();
            }
        });

        $("#j-btn-save-provider").click( function() {
            var firstName = $("#j-provider-fname").val();
            var middleName = $("#j-provider-mname").val();
            var lastName = $("#j-provider-lname").val();
            var npi = $("#j-provider-npi").val();
            self.saveProvider(firstName, middleName, lastName, npi).then( function() {
                $("#fancybox-close").click();
            });
        });

        $("#j-btn-add-provider").fancybox( {
            onComplete: function() {
                $("#modal-add-provider").find("#j-provider-fname").focus();
            },
            onClosed: function() {
                $("#modal-add-provider .j-field input").val("");
            }
        });

        // setup date fields
        Calendar.setup({inputField:"j-claim-date", ifFormat:"%Y-%m-%d", button:"j-claim-date-btn"});
        Calendar.setup({inputField:"j-received-date", ifFormat:"%Y-%m-%d", button:"j-received-date-btn"});
    });
};
