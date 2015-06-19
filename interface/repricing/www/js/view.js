function RepricingView(patientID, encounterID, mypcc){
    this.patientID = patientID;
    this.encounterID = encounterID;
    this.populateUser();
    this.setupForm();
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


RepricingView.prototype.populatePayors = function() {
    var deferred = Q.defer();
    var dateOfService = $("#j-claim-date").val();
    $("#j-payor-primary-selection option").remove();
    $.get('service/payors.php?patientID=' + this.patientID + '&dateOfService=' + dateOfService + '&ts=' + new Date().getTime(), null, function(data) {
        $.each( data, function(i, payor) {
            $("#j-payor-primary-selection").append("<option value='" + payor.payorID + "'>" +payor.payorName + " </option>");
        } );

        deferred.resolve();
    });
    $("#j-payor-primary-selection").append("<option value='-1'>Unassigned</option>");

    return deferred.promise;
};

RepricingView.prototype.buildClaimDetailEntry = function(serviceCodeID, serviceCode, serviceDate, serviceCharge, claimEntryDescription) {
    $("#j-claim-detail-list").show();
    var self = this;
    var newRow = $(".j-claim-detail-entry tr").clone();
    if ( serviceCodeID ) {
        newRow.find(".j-service-code").attr('data-service-code-ID', serviceCodeID);
    }
    if ( serviceCode ) {
        newRow.find('.j-service-code').val(serviceCode);
    }
    if ( claimEntryDescription ) {
        newRow.find('.j-claim-entry-description').html(claimEntryDescription);
    }

    // attach to DOM
    $("#j-claim-detail-list table").append(newRow);

    // setup calendar
    var receivedDate = serviceDate || $('#j-received-date').val();
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

    // setup service code
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
        }).focusout( function() {
            if (!$(this).attr('data-service-code-ID') && $(this).val().trim() ) {
                $(this).val("");
                newRow.find('.j-claim-entry-description').html( '--');
            }
        });

    // setup delete
    newRow.find('.j-claim-entry-delete-btn')
        .click( function() {
            newRow.remove();
            if ( $('.j-claim-detail-entry-row:visible').length < 1 ) {
                $("#j-claim-detail-list").hide();
            }
            self.recalculateBalances();
        });

    // setup service charge
    newRow.find('.j-service-charge')
        .change( function() {
            self.recalculateBalances();
        });

    if ( serviceCharge ) {
        newRow.find('.j-service-charge').val( serviceCharge );
        self.recalculateBalances();
    }
};

RepricingView.prototype.wireEventListeners = function() {
    var self = this;

    $("#j-btn-add-service").click( function() {

        self.buildClaimDetailEntry();
    });

    // setup provider
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

    // setup claim date and service date
    Calendar.setup({inputField:"j-claim-date", ifFormat:"%Y-%m-%d", button:"j-claim-date-btn", onUpdate : function() {
        self.populatePayors();
    } });
    Calendar.setup({inputField:"j-received-date", ifFormat:"%Y-%m-%d", button:"j-received-date-btn"});

    // setup total billed
    $("#j-total-billed").change( function() {
        self.recalculateBalances();
    });

    // setup received date
    var today = new Date();
    $("#j-received-date").val( today.format("yyyy-mm-dd") );

    // setup save claim
    $("#j-btn-add-save-claim").click( function() {
        var claimData = self.validateSave();
        if (claimData ) {
            self.saveClaim(claimData);
        }
    });

    return Q.resolve();
};

RepricingView.prototype.setupDynamicOptions = function() {
    function populateEOBStatuses() {
        var deferred = Q.defer();

        $("#j-payor-primary-selection").append("<option value='-1'>Unassigned</option>");
        $.get('service/eob_statuses.php?ts=' + new Date().getTime(), null, function(data) {
            $.each( data, function(i, eobStatus) {
                $("#j-eob-statuses").append("<option value='" + eobStatus.id + "'>" +eobStatus.label + " </option>");
            } );
            deferred.resolve();
        });

        return deferred.promise;
    }

    function setupClaimType() {
        var deferred = Q.defer();
        $.get('service/claim_types.php', null, function (data) {
            $.each(data, function (i, claimType) {
                $("#j-claim-type-selection").append("<option value='" + claimType.label + "'>" + claimType.label + " </option>");
            });
            deferred.resolve();
        });
        return deferred.promise;
    }

    return populateEOBStatuses().then( function() {
        return setupClaimType();
    });
};

RepricingView.prototype.populateClaim = function() {
    var deferred = Q.defer();
    var self = this;

    var encounterID = self.encounterID;
    if ( encounterID ) {
        $.get('service/claims.php?encounterID=' + encounterID + '&ts=' + new Date().getTime(), null, function(data) {
            console.log(data);
            var summary = data['summary'];
            $('#j-claim-date').val( summary['claimDate']);
            $('#j-provider')
                .val( summary['providerName'] )
                .attr('data-provider-ID', summary['providerID'] );
            $("#j-claim-type-selection").val(summary['claimType']);
            $("#j-eob-statuses").val(summary['eobStatus']);
            $("#j-eob-note").val(summary['eobNote']);

            var transactions = data['transactions'];
            var toCall = self.buildClaimDetailEntry;
            $.each( transactions, function( i, transaction ) {
                toCall.call( self,
                    transaction['serviceCode'].id,
                    transaction['serviceCode'].code,
                    transaction['serviceDate'],
                    transaction['charge'],
                    transaction['serviceCode'].text
                );
            });

            self.populatePayors()
                .then( function() {
                    $("#j-payor-primary-selection").val( summary['primaryPayorID']);
                });
        });
    } else {
        // its a new claim (eg. no encounter is specified)
        deferred.resolve();
    }

    return deferred.promise;
};

RepricingView.prototype.setupForm = function() {
    var self = this;
    $(document).ready( function() {
        self.wireEventListeners()
            .then( function () {
                return self.setupDynamicOptions();
            })
            .then( function() {
                return self.populateClaim();
            });
    });
};

RepricingView.prototype.validateSave = function() {
    var claimData = gatherClaimData();
    var errors = [];

    // inspect summary for errors
    if ( !claimData.summary.claimDate ) {
        errors.push('Date of service is required');
    }
    if ( !claimData.summary.receivedDate ) {
        errors.push('Received date is required');
    }
    if ( !claimData.summary.providerID ) {
        errors.push('Provider is required');
    }
    if ( !claimData.summary.totalBilled ) {
        errors.push('Total billed is required');
    }

    if ( claimData.transactions.length < 1 ) {
        errors.push('There must be at least one service.');
    } else {
        debugger;
        // inspect transactions for errors
        for ( var i = 1; i <= claimData.transactions.length; i++) {
            var service = claimData.transactions[i-1];
            if ( !service.serviceCodeID ) {
                errors.push( 'Service #' + i + ' is missing Service Code.');
            }
            if ( !service.transactionDate ) {
                errors.push( 'Service #' + i + ' is missing Service Date.');
            }
            if ( !service.charge ) {
                errors.push( 'Service #' + i + ' is missing charge amount.');
            }
            if ( !service.allowed ) {
                errors.push( 'Service #' + i + ' is missing allowed amount.');
            }
        }
    }

    if ( errors.length > 0) {
        var errorMsg = '';
        errorMsg += 'Please resolve the following errors before saving the EOB.\n';
        errorMsg += '\n';
        for (var i = 0; i < errors.length; i++ ) {
            errorMsg += errors[i] + '\n';
        }

        alert(errorMsg);
        return;
    }

    return claimData;
};

function gatherClaimData() {
    var self = this;
    // 1. scrape claim metadata from the UI
    var summary = {
        patientID: self.patientID,
        providerID: $("#j-provider").attr('data-provider-ID'),
        claimDate: $("#j-claim-date").val(),
        receivedDate: $("#j-received-date").val(),
        totalBilled: $("#j-total-billed").val(),
        claimType: $("#j-claim-type-selection").val(),
        primaryPayorID: $("#j-payor-primary-selection").val(),
        eobStatus: $("#j-eob-statuses").val(),
        eobNote: $("#j-eob-note").val()
    };

    var transactions = [];
    $.each($('.j-claim-detail-entry-row:visible'), function (i, transaction) {
        var row = $(transaction);
        var thisTransactionDate = row.find('.j-claim-detail-date').val();
        var thisCharge = parseFloat(row.find('.j-service-charge').val() || "0.0");
        var thisAllowedAmount = parseFloat(row.find('.j-service-allowed').val() || "0.0");
        var serviceCodeID = row.attr('data-service-code-ID');
        transactions.push({
            serviceCodeID: serviceCodeID,
            transactionDate: thisTransactionDate,
            charge: thisCharge,
            allowed: thisAllowedAmount
        });
    });
    return {self: self, summary: summary, transactions: transactions};
}

RepricingView.prototype.saveClaim = function(claimData) {
    var self = claimData.self;
    var summary = claimData.summary;
    var transactions = claimData.transactions;
    var claim = {
        summary: summary,
        transactions: transactions
    };

    // 2. call the backend to save it
    console.log(JSON.stringify(claim, null, 1));
    $.ajax({
        type: "POST",
        contentType: "application/json; charset=utf-8",
        url: "service/claims.php",
        data: JSON.stringify(claim),
        success: function (response) {
            // set active encounter
            var encounterID = response.summary.encounterID;
            var encounterDate = new Date(summary.claimDate).format("mm/dd/yyyy");
            parent.left_nav.setEncounter(encounterDate, encounterID, window.name);

            // update form model
            self.encounterID = encounterID;
        }
    });

};

function guid() {
    function s4() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    }
    return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
        s4() + '-' + s4() + s4() + s4();
}

