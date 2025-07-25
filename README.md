# WHMCS - Módulo de PIX e Boleto PagHiper 

Emissão de PIX de forma simplificada ( com retorno automático ).

Emita boletos bancários direto do seu WHMCS.

Boletos registrados conforme especificação da FEBRABAN. Baixa de pagamentos automática.

Seu boleto sai direto no PDF anexo a fatura (funcionalidade exclusiva).

* **Versão mais Recente:** 2.5.3
* **Requer WHMCS** versão mínima 5.0
* **Requisitos:** PHP >= 5.6.0, cURL e JSON ativado.
* **Compatibilidade:** WHMCS 8.X, PHP 7.x. Mod_rewrite opcional


# Como Instalar

1. Crie sua conta na PagHiper [clique aqui para saber como](https://github.com/paghiper/whmcs/wiki/Como-criar-seu-cadastro-na-PagHiper).

2. Baixe o gateway da **PagHiper**, extraia o conteúdo e faça upload das pastas includes e modules para a raíz da sua instalação do WHMCS

3. Dentro da área administrativa do seu WHMCS, vá em: Setup > Payments > Payment Gateways (em inglês) ou Opções > Pagamentos > Portais para Pagamento

4. Após, va na aba “All Payment Gateways” ou "Todos os Portais de Pagamento" e procure pelo modulo de nome: “PagHiper Boleto” e clique em cima.

5. Será exibida uma pagina semelhante a que se encontra na figura abaixo. Basta configurar com suas credenciais.

6. Repita o processo para a configuração do PIX:
Na aba “All Payment Gateways” ou "Todos os Portais de Pagamento" e procure pelo modulo de nome: “PagHiper PIX” e clique em cima.

7. Será exibida uma pagina semelhante a que se encontra na figura abaixo. Basta configurar com suas credenciais.

8. Adicione o texto abaixo no arquivo invoicepdf.tpl do seu tema, para anexar boletos e códigos PIX ao PDF das faturas (opcional)

```
<?php // Paghiper PHP template integration
	
// PHP 5.x compatibility
if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
    $basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
} else {
    $basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
}

include $basedir . '/modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php'; ?>
```

9. Para inserir código PIX e linha digitável de boletos, edite seu template de e-mail em Opções (Setup) > Modelos de e-mail (E-mail templates). Você pode usar dois campos de mesclagens, um para boleto e outro para PIX, respectivamente: {$linha_digitavel} e {$codigo_pix}. Basta inserí-los nos templates de comunicação de e-mail nos locais desejados.

Se tiver dúvidas sobre esse processo, acesse nosso [guia de configuração de plugin](https://github.com/paghiper/whmcs/wiki/Configurando-o-plugin-no-seu-WHMCS)


# Suporte

Para questões relacionadas a integração e plugin, acesse o [forum de suporte no Github](https://github.com/paghiper/whmcs/issues);
Para dúvidas comerciais e/ou sobre o funcionamento do serviço, visite a nossa [central de atendimento](https://www.paghiper.com/atendimento/).

# Changelog

## 2.5.3 - 2025/07/14

* Fix: Dados de clientes com parâmetros NULL
* Mais informações nos logs

## 2.5.2 - 2024/07/04

* Evita mensagens de validação e criação de transações quando o método de pagto. escolhido não for Paghiper
* Remoção de Warning relacionado a atualização do banco de dados

## 2.5.1

* Refatoramento do plug-in
* Mais estabilidade na execução
* Questões de compatibilidade com PHP 5.6 e WHMCS 7.1 <> 8.9.0
* Questões relacionadas a database design
* Maior velocidade de operação
* Mais informações de log
* Remoção de múltiplos warnings no geral

## 2.5

* Maior estabilidade e rapidez na execução das CRONs
* Compatibilidade com PHP 5.6 e WHMCS 7.x
* Refatoração dos hooks e integrações nos templates de e-mail
* Otimização da arquitetura do banco de dados

## 2.4.4

`Bugs resolvidos`
* PDF anexo falhava em algumas ocasiões

## 2.4.3

`Bugs resolvidos`
* Método de pagamento indisponível para a moeda selecionada
* CPF/CNPJ inválido! Cheque seu cadastro. (WHMCS 7/8)
* String + Float operator na integração com PDFs
* Boletos e PIXes não anexos quando processando via CRON

`Melhorias`

* Novo bloco de integração para PDFs anexos
* Checagem de ACL compatível com WHMCS8
* Refatoramento progressivo do plugin

`Melhorias`

## 2.4.2

`Bugs resolvidos`

* Label de transação (PIX/boleto) aparecendo trocado em algumas telas de erro
* Erro 0x004681 aparecendo sem motivo em alguns casos
* Problemas intermitentes com a atualização de status das faturas

`Melhorias`

* Mais dados para debug nos Logs de Portais de Pagamento

## 2.4.1

`Bugs resolvidos`

* Emissão falhava ao configurar o gateway com um user admin inválido (função de fallback falhava)
* Mensagem "Table exists/Table does not exist" aparecendo de maneira intermitente

## 2.4

`Bugs resolvidos`

* Operadores ternários mostravam erros, dependendo da configuração do PHP

`Melhorias`

* Compatibilidade com PHP 8.1
* Queries convertidas para uso da classe Capsule (ao invés de mysqli)


## 2.3

`Bugs resolvidos`

* Transações sendo pagas de maneira duplicada
* Transações PIX sendo exibidas a clientes em alguns casos, ao invés de re-emitidas
* Melhor validação de TaxID (CPF/CNPJ)
* Transações pagáveis sendo ignoradas, caso multa por atraso seja aplicada pelo WHMCS
* Warnings e Deprecated errors
* Dados de clientes sem cadastro não estavam disponíveis para emissão, em algumas circunstâncias
* Suporte a campo de razão social separado
* Número de telefone não estava sendo anexado a transação

`Melhorias`

* Validação de dados e campos aplicada no checkout do front-end
* Mais pontos e informações de log
* Refatoração e melhor eficiência de código


## 2.2.1

`Bugs resolvidos`

* Código PIX com quebra de linha indesejada no PDF anexo
* Crash ao marcar pedido como pago ou cancelado via painel
* Conciliação (adição de taxa ou desconto a fatura) impedindo a baixa

## 2.2

`Melhorias implementadas`

* Checagem e validação de CPF/CNPJ no checkout, na página de invoice e na tela de boleto/PIX
* Exibição das tags de boleto/PIX no editor de templates de e-mail
* Suporte a campo de razão social (opcional)
* Melhor lógica de reaproveitamento de boletos
* Mais informações nos logs
* Melhor manipulação de CPF/CNPJ para criação de faturas
* Tela de erro, caso o o valor com desconto por pagto. antecipado seja menor que R$ 3
* Tela de erro genérica (evita tela branca, caso uma transação não possa ser gerada)

`Bugs resolvidos`

* Boletos vencidos eram ignorados, ainda que dentro do período de tolerância
* Erro ao cancelar boletos nos logs
* Bloco de inserção de boleto/PIX PDF era executado apenas na primeira fatura da CRON (mod_lsapi)
* Melhor cálculo de desconto para pagto. antecipado
* Maior dinstinção entre as mensagens (evita confusão no front-end)
* Melhor convenção de naming de funções (evita conflitos com outros módulos/gateways)
* Warning de operador ternário removido


## 2.1

`Melhorias implementadas`

* Suporte nativo ao PIX PagHiper
* Processo de instalação simplificado
* Novas telas de status e ícones
* Pedidos com boleto agora levam o código de barras junto com a linha digitável
* Melhorias de segurança
* Refatoração completa do plug-in

`Bugs resolvidos`

* Em alguns casos, o campo de nome era utilizado na emissão do boleto, mesmo com CNPJ do cliente informado
* Só exibimos boleto e PIX para usuários com moeda em 

## 2.0.3

`Melhorias implementadas`

* Cancelamento automático de boletos (na baixa e pagto. parcial de faturas)

`Bugs resolvidos`

* PDF de fatura mostrava boleto em branco de maneira intermitente
* Pagamentos duplicados em alguns ambientes

## 2.0.2

`Melhorias implementadas`

* BUGFIX: Faturas parcialmente pagas agora são interpretadas corretamente
* Segurança: Maior proteção contra XSS e SQL injection
* Melhor precisão na determinação de nova data de vencimento, ao reemitir uma fatura vencida
* Novo texto descritivo para a multa proporcional no back-end
* Refatoração geral, redução do número de queries e mais

## 2.0.1.3 - 2019/10/14

`Bugs resolvidos`

* Conciliação de multa/desconto impedia baixa das faturas como esperado no WHMCS v7.8

## 2.0.1.2

`Bugs resolvidos`

* Boleto não era resgatado caso taxa personalizada estivesse sendo aplicada
* Multa fatorada e descrição fixa não funcionavam em circunstâncias específicas

## 2.0.1.1

`Bugs resolvidos`

* Boletos sendo emitidos para todas as faturas, independente do método de pagamento
* Possível erro 500 causado pelo invoicepdf.tpl (Adicionada tag de fechamento do php)

## 2.0.1

`Melhorias`

* Agora é possível configurar a nova data de vencimento para reemissão de boletos vencidos
* Possibilidade de restrição na emissão de novos boletos ou emitir para todas as faturas por padrão
* Novas telas de status
* Novo hook para criação do boleto junto com a criação da fatura
* Boleto anexo no e-mail da fatura (veja como ativar aqui)
* Seleção automática de usuário para uso na API Local do WHMCS (necessário para algumas operações internas)
* Novos pontos de log
* Seleção automática de usuário admin para uso na localAPI

`Bugs`

* Melhorada a lógica de busca de boletos emitidos (para faturas vencidas)
* Novo método de formação de URL de retorno

## 2.0

`Novas funcionalidades`

* Uso da nova API 2.0
* Re-utilização de boletos
* Juros/multas automáticos na emissão
* Desconto por pagto. antecipadotbladmin
* Conciliação (acréscimo ou decréscimo de valores) na compensação dos boletos
* Acesso direto ao boleto bancário (por link no e-mail, sem a necessidade de log-in)

`Melhorias`

* Adicionados endpoints para emissão do boleto bancário na emissão da fatura
* Possibilidade de integração do boleto nos e-mails de notificação (boleto PDF na fatura e linha digitável no corpo do e-mail)
* Adicionado novo status "Reservado", para pré-confirmação de pagto.
* Atualização do ícone usado no checkout
* Uso de classes bootstrap para melhor visual no checkout

`Bugs e correções`

* Log de notificação inválida estava armazenando array vazio, impossibilitando debug
* Uso do total da fatura (ao invés do sub-total) - Isso corrige problemas relacionadas a aplicação de taxas, créditos e descontos
* Compatibilidade com WHMCS 7.5
* Erro ao emitir/visualizar boletos através de uma sub-conta

## 1.2.1b

`Melhorias e novidades`

* Possibilidade de usar campos separados para CPF e CNPJ

`Bugs e correções`

* Coluna de valores estava sendo criada como FLOAT, causando erro nas queries em algumas versões do MySQL. Boletos são reutilizados corretamente agora.

## 1.2.1

`Melhorias e novidades`

* Atualização de ícone de boleto bancário
* Uso de classes bootstrap para melhor visual no checkout
* Maior clareza nas instruções e remoção de passos desnecessários
* Remoção da "integração avançada"
* Preparação para implementação de linha digitável e boleto anexo no e-mail da fatura

## 1.2

`Melhorias e novidades`

* Compatibilidade com WHMCS 7.5
* Implementação da nova API
* Armazenamento de boletos em tabela, para consulta e re-utilização
* Juros, Multas e desconto por pagto. antecipado
* Conciliação na baixa (para evitar multas como crédito)

`Bugs e correções`

* Uso do total da fatura para cálculo (ao invés do subtotal), para aplicação de descontos e créditos
* Problema nas queries de atualização da tabela mod_paghiper
* Erro ao emitir/visualizar boletos através de uma sub-conta

## 1.121

`Bugs e correções`

* Corrigido erro na formação da URL para recebimento de retorno

## 1.12

`Melhorias e novidades`

* Informa a versão do módulo
* Tabela criada para armazenar retornos da PagHiper.

`Bugs e correções`

* Cálculo de taxas no checkout transparente não era efetuado
* URL do sistema não era retornada caso o WHMCS estivesse instalado em subdiretório
* Caracteres nos nomes de campos personalizados vinham sem HTML entities

## 1.11

`Bugs e correções`

* Problemas relacionados ao checkout transparente
* Omissão de CPF/CNPJ

## 1.1

`Melhorias e novidades`

* Otimização geral e limpeza de código
* Suporte a Checkout transparente
* Integração avançada (campos recebidos podem ser salvos em uma tabela)
* Opção para abrir boleto por link direto
* Envia o nome do cliente em caso de CPF, razão social em caso de CNPJ

`Bugs e correções`

* Loga eventuais problemas com o checkout transparente para debug
* Trata o campo de CPF/CNPJ para se adequar ao formato exigido pela PagHiper
* Usa o nome de usuário admin por padrão, caso não seja informado


## 1.0

* Lançamento inicial

# Licença

Copyright 2016-2019 Serviços Online BR.

Licensed under the 3-Clause BSD License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

[https://opensource.org/licenses/BSD-3-Clause](https://opensource.org/licenses/BSD-3-Clause)

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
