# Pasos Siguientes SaaS RBAC

1. Separar datos por tenant:
   agregar `tenant_id` a tablas operativas como `eventos`, `gastos`, `solicitudes` e imponer filtros por tenant en cada consulta.

2. Crear onboarding de clientes:
   formulario para alta de cliente SaaS, creación de tenant, usuario admin inicial y plan asignado.

3. Automatizar cobro y bloqueo:
   registrar pagos, calcular próxima renovación y cambiar `subscription_status` a `past_due` o `suspended` según vencimiento.

4. Registrar auditoría:
   guardar quién cambió roles, estados, suscripciones y fechas de vigencia.

5. Reemplazar permisos estáticos por permisos desde tabla:
   leer `saas_roles`, `saas_permissions` y `saas_role_permissions` para que el RBAC sea administrable desde panel.
