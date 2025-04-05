/**
 * Filter Helper Function
 *
 * Allows rapid filtering of the rankings table.
 */
function filterRankings() {
    var input = document.getElementById('search-input');
    if (!input) return;

    var filter = input.value.toUpperCase();
    var table = document.querySelector('.rankings-table');
    if (!table) return;

    var tr = table.getElementsByTagName('tr');

    // Loop through table rows (skip header row).
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName('td')[1];
        if (td) {
            var txtValue = td.textContent || td.innerText;
            tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}
