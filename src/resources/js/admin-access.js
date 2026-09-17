document.querySelectorAll('[data-permission-row]').forEach((row) => {
    const view = row.querySelector('[data-view-permission]');
    const sync = () => row.querySelectorAll('[data-action-permission]').forEach((action) => {
        action.disabled = !view.checked;
        if (!view.checked) action.checked = false;
    });
    view.addEventListener('change', sync);
    sync();
});
document.querySelectorAll('[data-staff-access]').forEach((form) => {
    const nameInput = form.elements.namedItem('name');
    const agentOptions = Array.from(form.querySelectorAll('#staff-agent-names option'));
    const fillAgentContact = () => {
        const agent = agentOptions.find((option) => option.value === nameInput.value);
        if (!agent) return;
        nameInput.value = agent.dataset.agentName;
        form.elements.namedItem('email').value = agent.dataset.agentEmail || '';
        form.elements.namedItem('phone').value = agent.dataset.agentPhone || '';
    };
    nameInput.addEventListener('input', fillAgentContact);
    nameInput.addEventListener('change', fillAgentContact);

    const sync = () => {
        const permissions = new Set();
        form.querySelectorAll('[data-role-permissions]:checked').forEach((role) => JSON.parse(role.dataset.rolePermissions).forEach((permission) => permissions.add(permission)));
        form.querySelectorAll('[data-effective-module]').forEach((row) => {
            const module = row.dataset.effectiveModule;
            const labels = ['View'];
            Object.entries(JSON.parse(row.dataset.actionLabels)).forEach(([action, label]) => {
                if (permissions.has(`${module}.${action}`)) labels.push(label);
            });
            row.querySelector('[data-effective-label]').textContent = permissions.has(`${module}.view`) ? labels.join(', ') : 'Hidden / No Access';
        });
    };
    form.addEventListener('change', sync);
    sync();
});
