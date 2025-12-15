# Todos Devem ter os seguintes filtros

## HOSTS
- host groups
- hosts
- exclude hosts
- exclude host in maintenance

## PROBLEMS
- problem tags
- problem status (all, problem, resolved)  ======== Alarmwidget - (usar de base)  ==================
- show ack problems
- show supressed problems

## TRIGGERS
============== Alterar demais widgets para mesma organização do alarmwdiget no forms  ===============
- Show Not Classified
- Show Information
- Show Waring
- Show Average
- Show High
- Show Disaster

## Observações
Validar correlação dos alarmes, exibir e contar apenas aqueles que estão realmente ativos.

# Modulos Atuais - Histório

## Alarm Widdget
-> Está exibindo alarmes que possuem co-relação. Deve mostrar somente o ativo
  - Descobrir se é o mais recente, ou maior severidade do mesmo alarme.

## Hostgroupalarms

## Hostgroupstatus

## mapwidget

## text Widget

# Próximos Modulos

##Contador baseado em filtros
 - Hosts (Enable, Disable, Mainantence)
 - Alamrms (Problems, Supressed, ACK, por Serviridade)
 - Items (Ativos, Disativados, erro).



#Tarefas atuais Enzo:
(✓) ⏳
- Incluir filtro por tag e value no alarm widget. (1°) (✓)
  
- Averiguar todos os widgets para conferir se estão funcionando com essa funcionalidade, e consertar aqueles que não estão.  (2°) (✓)

- Incluir Problem Status nos demais widgets -> Alarmwidget para usar de base.  (3°) (✓)

- Alterar demais widgets para mesma organização do filtro de severidade, ficando igual ao do alarmwdiget no forms.  (4°) (✓)

- Incluir filtro de exibir somente os problemas suprimidos. (5°) (✓)

- Alterar pop-up do Hostgroupalarms para ficar igual ao do mapwidget. (6°) (✓)

- Ajustar completamente o widget table, usando o exemplo do github (7°)  ⏳
