// If you need to import jQuery
// import $ from 'jquery';

$(document).ready(function () {
    $("#addProductBtn").on("click", function () {
        $("#productIcon").addClass("hidden");
        $("#productSpinner").removeClass("hidden");
        $("#productButtonText").text("Loading...");
    });

    $("#addCategoryBtn").on("click", function () {
        $("#categoryIcon").addClass("hidden");
        $("#categorySpinner").removeClass("hidden");
        $("#categoryButtonText").text("Loading...");
    });

    $("#filterBtn").on("click", function () {
        $("#filterIcon").addClass("hidden");
        $("#filterSpinner").removeClass("hidden");
        $("#filterButtonText").text("Filtering...");
    });
});
