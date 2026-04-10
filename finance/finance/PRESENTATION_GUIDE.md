# UniFinance ERP: Presentation & Testing Script
This guide walks you through the 100% functional features of your Financial Management System for a seamless presentation.

## Phase 1: Authentication & Dashboard (Secure Entry)
1. **Login**: 
   - Open `login.php`.
   - Use Username: `admin_user` | Password: `admin123`.
   - **Note**: Point out the removal of "Register" for institutional security.
2. **Dashboard Overview**:
   - Show the **Live Analytics**: 
     - *Financial Performance Overview* (Bar chart with college-specific colors).
     - *Budget Utilization* (Doughnut chart showing real-time spending).
     - *Activity Monitor* (Sparkline showing transaction trends from the last 7 days).
3. **Smart Search**: 
   - Type "CCSE" or a student name like "Alice" in the top search bar.
   - Show how it suggests both features and actual database records.

---

## Phase 2: Student Billing & Revenue (Accounts Receivable)
1. **Create Invoice**:
   - Go to **Student Billing**.
   - Create an invoice for a new student.
   - **Verification**: Show the "Unpaid" status and the automatic Ledger entry (Debit AR, Credit Revenue).
2. **Process Payment**:
   - Click "Pay" on an unpaid invoice.
   - Enter a partial or full amount.
   - **Verification**: The status updates to "Partial" or "Paid" instantly.

---

## Phase 3: Procurement & Budgeting (Accounts Payable)
1. **Create Purchase Order (PO)**:
   - Go to **Procurement**.
   - Create a PO for a vendor (e.g., "Global Tech").
   - **High-Five Feature**: Upload a PDF or Image as a mock receipt/attachment.
2. **Budget Reservation**:
   - Show that the amount is now "Pending" in the **Approvals** queue.
   - Log out and log in as a `dept_id` or `finance_officer` if role-testing, or stay as admin to Approve.
3. **Mark Received**:
   - Once approved, click "Mark Received".
   - **Logic Check**: Explain that this hits the department's budget and moves the money to **Accounts Payable**.

---

## Phase 4: Financial Intelligence (Reports)
1. **Balance Sheet**:
   - Show that $Assets = Liabilities + Equity$ always balances.
2. **Income Statement**:
   - Show the Net Income (Revenue - Expenses).
3. **Audit Trail**:
   - Scroll to the bottom to show that every single click/action created a permanent **Journal Entry**.
4. **Export**: 
   - Click "Print" or "Export to Excel" to show professional document generation.

---

## Phase 5: Technical Resilience (Error Handling)
1. **Intentional Error**:
   - (Optional for advanced demo) Try to enter a negative amount in an invoice.
   - Show the **Application Shield** or custom toast notification preventing the invalid data.

---

### Shortcuts for your demo:
- **Admin Utilities**: Use this to show the raw Chart of Accounts balances.
- **Role Awareness**: Mention that different users (Students vs Admins) see different modules.

**Generated on April 11, 2026**
