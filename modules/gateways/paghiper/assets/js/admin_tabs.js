document.addEventListener("DOMContentLoaded", function() {
    // Only run if we are on the Payment Gateways config page for PagHiper
    var isPaghiper = false;
    var forms = document.querySelectorAll("form");
    forms.forEach(function(f) {
        if (f.innerHTML.indexOf('name="field[email]"') !== -1 && f.innerHTML.indexOf('paghiper') !== -1) {
            isPaghiper = true;
            initPaghiperTabs(f);
        }
    });

    function initPaghiperTabs(form) {
        var table = form.querySelector("table.form");
        if (!table) return;

        // Define groups
        var groups = {
            "Geral": ["nota", "FriendlyName", "email", "api_key", "token", "cpf_cnpj", "razao_social", "admin", "suporte"],
            "Taxas e Prazos": ["porcento", "taxa", "open_after_day_due", "reissue_unpaid", "late_payment_fine", "per_day_interest", "early_payment_discounts_days", "early_payment_discounts_cents"],
            "Avançado e Integração": ["issue_all", "tax_id_validation", "abrirauto", "fixed_description", "ui_injector"]
        };

        // Create Tab UI
        var tabContainer = document.createElement("ul");
        tabContainer.className = "nav nav-tabs";
        tabContainer.style.marginBottom = "15px";

        var tabContent = document.createElement("div");
        tabContent.className = "tab-content";

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

            // Tab click event
            a.addEventListener("click", function(e) {
                e.preventDefault();
                // Deactivate all
                tabContainer.querySelectorAll("li").forEach(function(el) { el.classList.remove("active"); });
                this.parentElement.classList.add("active");
                
                var activeGroup = this.dataset.group;
                
                // Show/hide rows
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
                    } else if (row.innerHTML.indexOf('ui_injector') !== -1 || row.innerHTML.indexOf('nota') !== -1 || row.innerHTML.indexOf('suporte') !== -1) {
                        // Handle pseudo-fields (Description only)
                        var text = row.innerText || row.textContent;
                        var matched = false;
                        groups[activeGroup].forEach(function(f) {
                            if (text.indexOf(f) !== -1 || row.innerHTML.indexOf(f) !== -1) matched = true;
                        });
                        row.style.display = matched ? "" : "none";
                    }
                });
            });

            first = false;
        }

        table.parentNode.insertBefore(tabContainer, table);
        
        // Trigger click on first tab
        tabContainer.querySelector("a").click();
        
        // Inject the Integration UI into the ui_injector row
        setupIntegrationUI();
    }

    function setupIntegrationUI() {
        // UI is loaded via PHP inside the Description of 'ui_injector'
        // Let's bind events for the buttons
        
        var forceBtn = document.getElementById('paghiper-force-integration');
        if (forceBtn) {
            forceBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var tpl = document.getElementById('paghiper-template-selector').value;
                submitAjaxAction('force_integration', { template: tpl });
            });
        }

        var restoreBtn = document.getElementById('paghiper-restore-backup');
        if (restoreBtn) {
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
        if(tplSelector) {
            tplSelector.addEventListener('change', function() {
                // Reload the page or fetch backups for the selected template via AJAX
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
        
        // We post to the current URL
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
