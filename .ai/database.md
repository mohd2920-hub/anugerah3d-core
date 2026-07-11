# MVP Tables

admins
agents
customers
categories
products
quotations
quotation_items
orders
order_items
invoices
activities
settings

## Current Implemented Tables

### usr_agent

- login_id varchar(100), unique
- agt_name varchar(150)
- id_number varchar(50), nullable, unique
- email varchar(100), unique
- phone_number varchar(50), nullable
- password varchar
- remember_token varchar, nullable
- agt_status varchar(15): active, inactive, blocked, suspended
- address varchar(250), nullable
- city varchar(100), nullable
- state varchar(100), nullable, selected from data_state.name
- discount_percentage decimal(5,1)
- profile_picture varchar(250), nullable, stores S3 object key/link
- last_login_at timestamp, nullable
- last_login_ip varchar(45), nullable
- total_sale decimal(8,2)
- created_at / updated_at timestamps

### data_state

- code varchar(20), unique
- name varchar(100), unique
