
@startuml
!define RECTANGLE class

' Actors positioned: 2 left, 2 right
actor "Student" as Student
actor "Faculty" as Faculty  
actor "Staff" as Staff
actor "Admin" as Admin

' Force positioning: 2 left, 2 right
' Left side actors
Student -[hidden]-> UC_LOGIN
Faculty -[hidden]-> UC_LOGIN

' Right side actors
Admin -[hidden]-> UC_VIEW_LOGS
Staff -[hidden]-> UC_VIEW_APPOINTMENTS

' Additional positioning to force layout
Student -[hidden]-> UC_BOOK_APPOINTMENTS
Faculty -[hidden]-> UC_ACCESS_PATIENT_PROFILES
Staff -[hidden]-> UC_ISSUE_PRESCRIPTIONS
Admin -[hidden]-> UC_MANAGE_USERS 

rectangle "Clinic Management System" {
  ' Core Authentication (top section)
  usecase "User Authentication" as UC_AUTH
  usecase "Login to System" as UC_LOGIN
  usecase "Logout from System" as UC_LOGOUT
  usecase "Reset Password" as UC_PASSWORD_RESET
  usecase "Verify Date of Birth" as UC_VERIFY_DOB

  ' Patient/Student use cases (upper middle)
  usecase "Book Appointments" as UC_BOOK_APPOINTMENTS
  usecase "Cancel Appointments" as UC_CANCEL_APPOINTMENTS
  usecase "View Medical History" as UC_VIEW_MEDICAL_HISTORY
  usecase "View Prescriptions" as UC_VIEW_PRESCRIPTIONS
  usecase "View Profile" as UC_VIEW_PROFILE
  usecase "Receive Notifications" as UC_RECEIVE_NOTIFICATIONS
  usecase "Send Messages" as UC_SEND_MESSAGES

  ' Medical staff use cases (middle section)
  usecase "View Appointments" as UC_VIEW_APPOINTMENTS
  usecase "Issue Prescriptions" as UC_ISSUE_PRESCRIPTIONS
  usecase "Access Patient Profiles" as UC_ACCESS_PATIENT_PROFILES
  usecase "Record Vital Signs" as UC_RECORD_VITALS
  usecase "Manage Referrals" as UC_MANAGE_REFERRALS
  usecase "Update Patient Info" as UC_UPDATE_PATIENT_INFO
  usecase "Send Parent Alerts" as UC_SEND_PARENT_ALERTS
  usecase "Manage Inventory" as UC_MANAGE_INVENTORY

  ' Administrative use cases (lower section)
  usecase "Manage Users" as UC_MANAGE_USERS
  usecase "Manage Patient Records" as UC_MANAGE_PATIENTS
  usecase "Import Student Data" as UC_IMPORT_STUDENTS
  usecase "Generate/View/Export Reports" as UC_REPORTS
  usecase "System Configuration" as UC_SYSTEM_CONFIG
  usecase "View System Logs" as UC_VIEW_LOGS
  usecase "Manage Staff Profiles" as UC_MANAGE_STAFF
  usecase "Data Backup" as UC_DATA_BACKUP
  usecase "Send Email Notifications" as UC_EMAIL_NOTIFICATIONS
}

' ===== ACTOR INTERACTIONS =====
' Student interactions (top level)
Student --> UC_LOGIN
Student --> UC_LOGOUT
Student --> UC_BOOK_APPOINTMENTS
Student --> UC_CANCEL_APPOINTMENTS
Student --> UC_VIEW_MEDICAL_HISTORY
Student --> UC_VIEW_PRESCRIPTIONS
Student --> UC_VIEW_PROFILE
Student --> UC_RECEIVE_NOTIFICATIONS
Student --> UC_SEND_MESSAGES

' Faculty interactions (top level)
Faculty --> UC_LOGIN
Faculty --> UC_LOGOUT
Faculty --> UC_ACCESS_PATIENT_PROFILES
Faculty --> UC_VIEW_MEDICAL_HISTORY
Faculty --> UC_BOOK_APPOINTMENTS
Faculty --> UC_RECEIVE_NOTIFICATIONS
Faculty --> UC_SEND_MESSAGES
Faculty --> UC_VIEW_PROFILE

' Staff (medical personnel) interactions (middle level)
Staff --> UC_LOGIN
Staff --> UC_LOGOUT
Staff --> UC_VIEW_APPOINTMENTS
Staff --> UC_MANAGE_PATIENTS
Staff --> UC_ISSUE_PRESCRIPTIONS
Staff --> UC_MANAGE_INVENTORY
Staff --> UC_RECORD_VITALS
Staff --> UC_MANAGE_REFERRALS
Staff --> UC_SEND_PARENT_ALERTS
Staff --> UC_UPDATE_PATIENT_INFO
Staff --> UC_SEND_MESSAGES

' Admin interactions (bottom level)
Admin --> UC_LOGIN
Admin --> UC_LOGOUT
Admin --> UC_MANAGE_USERS
Admin --> UC_MANAGE_PATIENTS
Admin --> UC_IMPORT_STUDENTS
Admin --> UC_REPORTS
Admin --> UC_SYSTEM_CONFIG
Admin --> UC_VIEW_LOGS
Admin --> UC_MANAGE_STAFF
Admin --> UC_DATA_BACKUP

' ===== RELATIONSHIPS =====
' Authentication include relationships (core functionality)
UC_LOGIN ..> UC_AUTH : <<include>>
UC_LOGOUT ..> UC_AUTH : <<include>>
UC_BOOK_APPOINTMENTS ..> UC_AUTH : <<include>>
UC_VIEW_APPOINTMENTS ..> UC_AUTH : <<include>>
UC_MANAGE_USERS ..> UC_AUTH : <<include>>

' Optional/conditional behaviors (extends)
UC_VERIFY_DOB .> UC_LOGIN : <<extend>>
UC_PASSWORD_RESET .> UC_LOGIN : <<extend>>

' Notification relationships (email notifications extended by alerts/notifications)
UC_SEND_PARENT_ALERTS .> UC_EMAIL_NOTIFICATIONS : <<extend>>
UC_RECEIVE_NOTIFICATIONS .> UC_EMAIL_NOTIFICATIONS : <<extend>>
@enduml
