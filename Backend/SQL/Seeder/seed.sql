USE `HemoRS`;

-- Desabilita verificações temporariamente para garantir a inserção limpa
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `doacao_historico`;
TRUNCATE TABLE `bolsa`;
TRUNCATE TABLE `doacao`;
TRUNCATE TABLE `doador`;
TRUNCATE TABLE `unidade`;
TRUNCATE TABLE `usuario`;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- 1. UNIDADES DE ATENDIMENTO (3 unidades)
-- ==============================================================================
INSERT INTO `unidade` (`id`, `nome`, `cidade`, `capacidade_diaria`) VALUES
(1, 'Hemocentro Central', 'Porto Alegre', 80),
(2, 'Hemocentro Regional de Caxias do Sul', 'Caxias do Sul', 40),
(3, 'Hemocentro Regional de Pelotas', 'Pelotas', 35);

-- ==============================================================================
-- 2. USUÁRIOS DO SISTEMA (3 usuários - 1 de cada perfil)
-- Senhas em hash Bcrypt fictício ($2y$10$...)
-- ==============================================================================
INSERT INTO `usuario` (`id`, `nome`, `email`, `senha`, `perfil`, `status`) VALUES
(1, 'Juliana Silva', 'recepcao@hemo.rs.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recepcao', 'ativo'),
(2, 'Carlos Eduardo Enf', 'enfermagem@hemo.rs.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enfermagem', 'ativo'),
(3, 'Mariana Costa', 'gestao@hemo.rs.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestor', 'ativo');

-- ==============================================================================
-- 3. DOADORES (15 doadores variados em sexo, tipo sanguíneo e idades)
-- CPFs válidos sem pontuação
-- ==============================================================================
INSERT INTO `doador` (`id`, `nome`, `cpf`, `data_de_nascimento`, `sexo`, `tipo_sanguineo`, `telefone`, `email`, `status`, `autorizacao_responsavel`) VALUES
(1, 'Lucas Oliveira', '91238475012', '1995-03-12', 'masculino', 'O-', '51988881111', 'lucas.oliveira@gmail.com', 'ativo', NULL),
(2, 'Ana Paula Souza', '45829301044', '1998-07-25', 'feminino', 'A+', '51988882222', 'ana.souza@gmail.com', 'ativo', NULL),
(3, 'Matheus Henrique', '10928374055', '1988-11-03', 'masculino', 'O+', '54988883333', 'matheus.h@gmail.com', 'ativo', NULL),
(4, 'Fernanda Lima', '67382910088', '1992-01-15', 'feminino', 'B+', '53988884444', 'fer.lima@gmail.com', 'ativo', NULL),
(5, 'Gabriel Santos', '38291048033', '2009-05-10', 'masculino', 'A-', '51988885555', 'gabriel.santos@gmail.com', 'ativo', 'sim'), -- Menor de idade (17 anos)
(6, 'Camila Rodrigues', '84920138099', '2000-09-30', 'feminino', 'AB+', '51988886666', 'camila.rodrigues@gmail.com', 'ativo', NULL),
(7, 'Rafael Alves', '29381049022', '1985-04-18', 'masculino', 'O-', '54988887777', 'rafael.alves@gmail.com', 'ativo', NULL),
(8, 'Beatriz Mendes', '50192837011', '1997-12-05', 'feminino', 'B-', '53988888888', 'bea.mendes@gmail.com', 'ativo', NULL),
(9, 'Thiago Barbosa', '73920184066', '1990-08-22', 'masculino', 'AB-', '51988889999', 'thiago.b@gmail.com', 'ativo', NULL),
(10, 'Juliana Martins', '19283746000', '1994-02-28', 'feminino', 'A+', '51999991111', 'ju.martins@gmail.com', 'ativo', NULL),
(11, 'Rodrigo Carvalho', '64830291077', '1983-06-14', 'masculino', 'O+', '54999992222', 'rodrigo.c@gmail.com', 'ativo', NULL),
(12, 'Patricia Gomez', '30291847055', '2001-10-08', 'feminino', 'O-', '53999993333', 'patricia.g@gmail.com', 'ativo', NULL),
(13, 'Felipe Nogueira', '82910384022', '1996-04-03', 'masculino', 'A+', '51999994444', 'felipe.nog@gmail.com', 'ativo', NULL),
(14, 'Larissa Costa', '49201837044', '1999-01-20', 'feminino', 'B+', '51999995555', 'larissa.costa@gmail.com', 'inativo', NULL), -- Doador Inativo
(15, 'Marcelo Silva', '10293847088', '1991-07-11', 'masculino', 'O+', '54999996666', 'marcelo.silva@gmail.com', 'ativo', NULL);

-- ==============================================================================
-- 4. DOAÇÕES (Cenários variados que exercitam as regras de negócio)
-- ==============================================================================
INSERT INTO `doacao` (`id`, `doador_id`, `unidade_id`, `data_e_hora_agendada`, `status`, `peso`, `hemoglobina`, `motivo_da_recusa`, `volume_coletado`, `coletado_em`, `usuario_id`) VALUES
-- 1. Doação ontem (Doador 1) -> Coletada
(1, 1, 1, '2026-08-17 09:00:00', 'coletada', 78, 15, NULL, 450, '2026-08-17 09:30:00', 2),

-- 2. Doação há ~100 dias (Doador 2 - Feminino, apta a doar novamente pois já passou de 90 dias)
(2, 2, 1, '2026-05-10 14:00:00', 'coletada', 62, 13, NULL, 450, '2026-05-10 14:25:00', 2),

-- 3 a 6. Doador 3 (Homem) -> Bateu o limite máximo de 4 doações em 12 meses
(3, 3, 2, '2025-09-01 10:00:00', 'coletada', 85, 16, NULL, 450, '2025-09-01 10:20:00', 2),
(4, 3, 2, '2025-12-01 10:00:00', 'coletada', 85, 15, NULL, 450, '2025-12-01 10:20:00', 2),
(5, 3, 2, '2026-03-01 10:00:00', 'coletada', 84, 16, NULL, 450, '2026-03-01 10:20:00', 2),
(6, 3, 2, '2026-06-01 10:00:00', 'coletada', 85, 16, NULL, 450, '2026-06-01 10:20:00', 2),

-- 7. Doação há 35 dias (Doador 4 - B+) -> Gera bolsa que VENCE HOJE / EM BREVE
(7, 4, 3, '2026-07-14 08:30:00', 'coletada', 58, 14, NULL, 450, '2026-07-14 08:50:00', 2),

-- 8. Doação há 45 dias (Doador 5 - A-) -> Bolsa já vencida / descartada
(8, 5, 1, '2026-07-04 11:00:00', 'coletada', 65, 14, NULL, 450, '2026-07-04 11:30:00', 2),

-- 9. Doação hoje agendada (Doador 6)
(9, 6, 1, '2026-08-18 10:00:00', 'agendada', NULL, NULL, NULL, NULL, NULL, 1),

-- 10. Doação recusada na triagem por baixa hemoglobina (Doador 7)
(10, 7, 2, '2026-08-18 11:00:00', 'recusada', 70, 11, 'Anemia detectada na triagem (Hb < 13 g/dL para homens)', NULL, NULL, 2),

-- 11. Doação cancelada pelo doador (Doador 8)
(11, 8, 3, '2026-08-15 15:00:00', 'cancelada', NULL, NULL, 'Desistência do doador por motivo pessoal', NULL, NULL, 1),

-- 12. Doação antiga há 20 dias (Doador 9) -> Bolsa transfundida
(12, 9, 1, '2026-07-29 13:00:00', 'coletada', 90, 16, NULL, 450, '2026-07-29 13:20:00', 2),

-- 13. Doação há 10 dias (Doador 10) -> Bolsa reservada para paciente
(13, 10, 1, '2026-08-08 09:00:00', 'coletada', 68, 13, NULL, 450, '2026-08-08 09:30:00', 2);

-- ==============================================================================
-- 5. BOLSAS DE SANGUE (Processadas a partir das doações coletadas)
-- Validade padrão de hemácias: 35 a 42 dias após a coleta
-- ==============================================================================
INSERT INTO `bolsa` (`id`, `doacao_id`, `codigo`, `tipo_sanguineo`, `coletado_em`, `vence_em`, `status`) VALUES
-- Bolsa da doação de ontem (Doador 1 - O-) -> Disponível e com validade longa
(1, 1, 'BOL-20260817-001', 'O-', '2026-08-17 09:30:00', '2026-09-21 09:30:00', 'disponivel'),

-- Bolsa de 100 dias atrás (Doador 2 - A+) -> Já transfundida
(2, 2, 'BOL-20260510-002', 'A+', '2026-05-10 14:25:00', '2026-06-14 14:25:00', 'transfundida'),

-- Bolsas do doador 3 (Histórico antigo de doações transfundidas)
(3, 3, 'BOL-20250901-003', 'O+', '2025-09-01 10:20:00', '2025-10-06 10:20:00', 'transfundida'),
(4, 4, 'BOL-20251201-004', 'O+', '2025-12-01 10:20:00', '2026-01-05 10:20:00', 'transfundida'),
(5, 5, 'BOL-20260301-005', 'O+', '2026-03-01 10:20:00', '2026-04-05 10:20:00', 'transfundida'),
(6, 6, 'BOL-20260601-006', 'O+', '2026-06-01 10:20:00', '2026-07-06 10:20:00', 'transfundida'),

-- Bolsa de 35 dias atrás (Doador 4 - B+) -> Prestes a vencer / Próxima do limite
(7, 7, 'BOL-20260714-007', 'B+', '2026-07-14 08:50:00', '2026-08-18 23:59:59', 'disponivel'),

-- Bolsa de 45 dias atrás (Doador 5 - A-) -> Descartada por vencimento
(8, 8, 'BOL-20260704-008', 'A-', '2026-07-04 11:30:00', '2026-08-08 11:30:00', 'descartada'),

-- Bolsa de 20 dias atrás (Doador 9 - AB-) -> Transfundida recentemente
(9, 12, 'BOL-20260729-009', 'AB-', '2026-07-29 13:20:00', '2026-09-02 13:20:00', 'transfundida'),

-- Bolsa de 10 dias atrás (Doador 10 - A+) -> Reservada para cirurgia
(10, 13, 'BOL-20260808-010', 'A+', '2026-08-08 09:30:00', '2026-09-12 09:30:00', 'reservada');

-- ==============================================================================
-- 6. HISTÓRICO DE AUDITORIA DAS DOAÇÕES
-- ==============================================================================
INSERT INTO `doacao_historico` (`id`, `doacao_id`, `status_de_origem`, `status_de_destino`, `usuario_id`, `motivo`, `data_e_hora`) VALUES
(1, 1, 'agendada', 'triagem', 1, 'Doador apresentou-se na recepção', '2026-08-17 09:05:00'),
(2, 1, 'triagem', 'coletada', 2, 'Triagem clínica e hematológica aprovada', '2026-08-17 09:30:00'),
(3, 10, 'agendada', 'triagem', 1, 'Doador apresentou-se na recepção', '2026-08-18 11:02:00'),
(4, 10, 'triagem', 'recusada', 2, 'Inaptidão temporária: Nível de hemoglobina abaixo do mínimo exigido', '2026-08-18 11:15:00'),
(5, 11, 'agendada', 'cancelada', 1, 'Cancelado via telefone a pedido do doador', '2026-08-15 14:00:00');