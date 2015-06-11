function RepricingView(){
}

RepricingView.prototype.buildClaimDetailEntry = function() {
    var newRow = $(".j-claim-detail-entry tr").clone();
    newRow.find(".j-service-code").focus();

    // attach to DOM
    $("#j-claim-detail-list table").append(newRow);
};

RepricingView.prototype.wireEventListeners = function() {
    var self = this;

    $("#j-btn-add-service").click( function() {
        self.buildClaimDetailEntry();
    });

};
