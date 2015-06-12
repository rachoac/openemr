function RepricingView(){
}

RepricingView.prototype.buildClaimDetailEntry = function() {
    var newRow = $(".j-claim-detail-entry tr").clone();
    newRow.find(".j-service-code").focus();

    // attach to DOM
    $("#j-claim-detail-list table").append(newRow);

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

RepricingView.prototype.wireEventListeners = function() {
    var self = this;

    $("#j-btn-add-service").click( function() {
        $("#j-claim-detail-list").show();

        self.buildClaimDetailEntry();
    });

};
