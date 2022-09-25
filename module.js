const select = document.getElementById('id_workflow_type_select');

select.addEventListener('change', function handleChange(event) {
    // console.log(event.target.value); // 👉️ get selected VALUE
    let url = window.location.href;

    let url_o = new URL(url);
    let params = new URLSearchParams(window.location.search);

    params.set('select', event.target.value);

    window.onbeforeunload = null;
    window.location.href = url_o.origin + url_o.pathname + '?' + params.toString();
})