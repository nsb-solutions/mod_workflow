const workflow_type_select = document.getElementById('id_workflow_type_select');
const assignment_select = document.getElementById('fitem_id_assignment_select');
const quiz_select = document.getElementById('fitem_id_quiz_select');

const reason_select = document.getElementById('id_reason_select');
const other_select = document.getElementById('fitem_id_other_reason');

if(quiz_select !== null) {
    quiz_select.style.display = 'none'
    workflow_type_select.addEventListener('change', function handleChange(event) {
        if (event.target.value === 'assignment') {
            assignment_select.style.display = 'flex';
            quiz_select.style.display = 'none';
        } else if (event.target.value === 'quiz') {
            assignment_select.style.display = 'none'
            quiz_select.style.display = 'flex'
        } else {
            assignment_select.style.display = 'none'
            quiz_select.style.display = 'none'
        }
    });
}

if(reason_select !== null) {
    other_select.style.display = 'none';
    reason_select.addEventListener('change', function handleChange(event) {
        if (event.target.value === 'other') {
            other_select.style.display = 'flex';
        } else {
            other_select.style.display = 'none';
        }
    });
}

