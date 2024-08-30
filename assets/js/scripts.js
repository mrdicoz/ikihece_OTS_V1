/* scripts.js */

document.addEventListener('DOMContentLoaded', function() {
    // Yazdırma işlevi
    window.printTable = function() {
        var divToPrint = document.getElementById("resultsTable");
        var newWin = window.open("");
        newWin.document.write('<html><head><title>Print</title>');
        newWin.document.write('<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">');
        newWin.document.write('</head><body>');
        newWin.document.write(divToPrint.outerHTML);
        newWin.document.write('</body></html>');
        newWin.print();
        newWin.close();
    };
});

