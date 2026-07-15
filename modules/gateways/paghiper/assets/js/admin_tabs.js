document.addEventListener("DOMContentLoaded", function() {
    if (window.paghiperTabsInitialized) return;
    window.paghiperTabsInitialized = true;

    var isPaghiper = false;
    var forms = document.querySelectorAll("form");
    forms.forEach(function(f) {
        if (f.innerHTML.indexOf('name="field[email]"') !== -1 && f.innerHTML.indexOf('paghiper') !== -1) {
            isPaghiper = true;
            initPaghiperTabs(f);
        }
    });

    function initPaghiperTabs(form) {
        if (form.dataset.paghiperInit) return;
        form.dataset.paghiperInit = "1";

        var table = form.querySelector("table.form");
        if (!table) return;

        var groups = {
            "Geral": ["nota", "FriendlyName", "email", "api_key", "token", "cpf_cnpj", "razao_social", "admin", "suporte"],
            "Taxas e Prazos": ["porcento", "taxa", "open_after_day_due", "reissue_unpaid", "late_payment_fine", "per_day_interest", "early_payment_discounts_days", "early_payment_discounts_cents"],
            "Templates de E-mail": ["email_templates", "ui_email_templates"],
            "Avançado e Integração": ["issue_all", "tax_id_validation", "abrirauto", "fixed_description", "ui_injector"]
        };

        var tabContainer = document.createElement("ul");
        tabContainer.className = "nav nav-tabs";
        tabContainer.style.marginBottom = "15px";

        var first = true;
        for (var groupName in groups) {
            var li = document.createElement("li");
            li.className = first ? "active" : "";
            var a = document.createElement("a");
            a.href = "#";
            a.innerHTML = groupName;
            a.dataset.group = groupName;
            li.appendChild(a);
            tabContainer.appendChild(li);

            a.addEventListener("click", function(e) {
                e.preventDefault();
                tabContainer.querySelectorAll("li").forEach(function(el) { el.classList.remove("active"); });
                this.parentElement.classList.add("active");
                
                var activeGroup = this.dataset.group;
                
                var rows = table.querySelectorAll("tr");
                rows.forEach(function(row) {
                    var input = row.querySelector("[name^='field[']");
                    if (input) {
                        var fieldNameMatch = input.name.match(/field\[(.*?)\]/);
                        if (fieldNameMatch) {
                            var fieldName = fieldNameMatch[1];
                            if (groups[activeGroup].indexOf(fieldName) !== -1) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        }
                    } else {
                        // Handle pseudo-fields with wrappers
                        if (row.querySelector('#paghiper_row_nota')) { row.style.display = (activeGroup === 'Geral') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_suporte')) { row.style.display = (activeGroup === 'Geral') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_ui_injector')) { row.style.display = (activeGroup === 'Avançado e Integração') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_ui_email_templates')) { row.style.display = (activeGroup === 'Templates de E-mail') ? '' : 'none'; }
                    }
                });
            });
            first = false;
        }

        table.parentNode.insertBefore(tabContainer, table);
        
        tabContainer.querySelector("a").click();
        
        setupIntegrationUI();
        setupEmailTemplatesUI(form);
    }

    function setupEmailTemplatesUI(form) {
        var hiddenRow = form.querySelector('#paghiper_row_email_templates_hidden');
        if (!hiddenRow) return;
        
        // Find the actual hidden input (the field `email_templates` rendered by WHMCS)
        // Since it's a 'text' field, WHMCS renders it as <input type="text" name="field[email_templates]">
        // The hiddenRow div is in the description. So we look up the tree to find the tr, then find the input.
        var parentTr = hiddenRow.closest('tr');
        if(parentTr) parentTr.style.display = 'none'; // Hide the entire row containing the native text input
        
        var inputEl = form.querySelector('input[name="field[email_templates]"]');
        if (!inputEl) return;
        
        var uiContainer = document.getElementById('paghiper_row_ui_email_templates');
        if (!uiContainer) return;
        
        var availableTemplates = [
            'Invoice Created', 
            'Invoice Payment Reminder', 
            'First Invoice Overdue Notice', 
            'Second Invoice Overdue Notice', 
            'Third Invoice Overdue Notice'
        ];
        
        var currentValues = inputEl.value.split(',').map(s => s.trim()).filter(s => s !== '');
        
        var html = '<div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">';
        html += '<p>Selecione os e-mails nos quais o boleto ou código PIX serão anexados automaticamente (requer Integração do PDF ativada).</p>';
        html += '<div class="checkbox-list">';
        
        availableTemplates.forEach(function(tpl) {
            var checked = currentValues.indexOf(tpl) !== -1 ? 'checked' : '';
            html += '<label style="display:block; margin-bottom:5px; font-weight:normal;">';
            html += '<input type="checkbox" class="paghiper-email-tpl-cb" value="'+tpl+'" '+checked+'> ' + tpl;
            html += '</label>';
        });
        
        html += '</div></div>';
        uiContainer.innerHTML = html;
        
        // Listen to changes and update the hidden text input
        var checkboxes = uiContainer.querySelectorAll('.paghiper-email-tpl-cb');
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var selected = [];
                uiContainer.querySelectorAll('.paghiper-email-tpl-cb:checked').forEach(function(checkedCb) {
                    selected.push(checkedCb.value);
                });
                inputEl.value = selected.join(',');
            });
        });
    }

    function setupIntegrationUI() {
        var forceBtn = document.getElementById('paghiper-force-integration');
        if (forceBtn && !forceBtn.dataset.bound) {
            forceBtn.dataset.bound = "1";
            forceBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var tpl = document.getElementById('paghiper-template-selector').value;
                submitAjaxAction('force_integration', { template: tpl });
            });
        }

        var restoreBtn = document.getElementById('paghiper-restore-backup');
        if (restoreBtn && !restoreBtn.dataset.bound) {
            restoreBtn.dataset.bound = "1";
            restoreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var tpl = document.getElementById('paghiper-template-selector').value;
                var backup = document.getElementById('paghiper-backup-selector').value;
                if (!backup) {
                    alert("Selecione um backup para restaurar.");
                    return;
                }
                if (confirm("Tem certeza que deseja restaurar este backup? O arquivo atual será substituído.")) {
                    submitAjaxAction('restore_backup', { template: tpl, backup: backup });
                }
            });
        }
        
        var tplSelector = document.getElementById('paghiper-template-selector');
        if(tplSelector && !tplSelector.dataset.bound) {
            tplSelector.dataset.bound = "1";
            tplSelector.addEventListener('change', function() {
                submitAjaxAction('get_status', { template: this.value }, function(res) {
                    if(res.status) {
                        document.getElementById('paghiper-int-status').innerHTML = res.integrated ? '<span style="color:green;font-weight:bold;">Integrado</span>' : '<span style="color:red;font-weight:bold;">Não Integrado</span>';
                        
                        var backupSel = document.getElementById('paghiper-backup-selector');
                        backupSel.innerHTML = '';
                        if(res.backups && res.backups.length > 0) {
                            res.backups.forEach(function(b) {
                                var opt = document.createElement('option');
                                opt.value = b.filename;
                                opt.innerHTML = b.filename + " (" + b.date + ")";
                                backupSel.appendChild(opt);
                            });
                            document.getElementById('paghiper-restore-backup').disabled = false;
                        } else {
                            var opt = document.createElement('option');
                            opt.value = "";
                            opt.innerHTML = "Nenhum backup disponível";
                            backupSel.appendChild(opt);
                            document.getElementById('paghiper-restore-backup').disabled = true;
                        }
                    }
                });
            });
        }
    }

    function submitAjaxAction(action, data, callback) {
        var formData = new FormData();
        formData.append('paghiper_action', action);
        for (var key in data) {
            formData.append(key, data[key]);
        }
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (callback) {
                callback(res);
            } else {
                if(res.success) {
                    alert("Ação realizada com sucesso!");
                    window.location.reload();
                } else {
                    alert("Erro: " + (res.error || "Desconhecido"));
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert("Erro na requisição AJAX.");
        });
    }
});
