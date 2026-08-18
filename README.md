# HemoRS

# Questões:

1. Onde exatamente está a checagem de que faz 60 dias da última doação?
e o que garante que ninguém pula ela?

- A regra de checagem está em DoacaoController em validarIntervaloEQuanidadeDoacoes(), o que garante que ninguém pula ela é o próprio fluxo que valida antes de agendar uma doação.

2. Duas recepcionistas agendam ao mesmo tempo na última vaga do dia. O que acontece?

- Uma recepcionista vai conseguir agendar a doação e a outra vai receber uma erro na tela dizendo que atingiu a lotação máximo, o próprio sistema, lumen e banco de dados têm delay e isso é quase improvável de acontecer.

3. Duas coletas terminam no mesmo segundo. Os dois códigos de bolsa saem diferentes?

- Os dois códigos saem sim diferente, pois ele pega o ultimo dígito pelo id do agendamento, então não tem como serem iguais.

4. A coleta grava em três lugares: doação, bolsa e histórico. Se o terceiro falhar, como fica o banco?

- O banco fica sem o histórico daquela doação.

5. Alguém manda DELETE /doadores/7 num doador com 4 doações. O que volta, e com qual status?

- Se o doador já tiver associado a uma doação, irá vir um erro dizendo que está impossibilitado de ser deletado e ao invés de deletar, o próprio sistema automaticamente inativa o doador.

6. A recepção manda PATCH /doacoes/3/coleta com o token dela. O que volta?

- O sistema volta uma mensagem de erro dizendo: "Usuário sem permissão".

e se mandar em uma doação que já está coletada? Não muda nada.

6. A listagem recebe ?ordem=alguma_coisa_estranha. O que acontece com a query?

- O banco de dados não recebe por conta do lumen não deixar e ser seguro.