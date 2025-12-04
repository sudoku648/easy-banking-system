import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { LocaleProvider } from './contexts/LocaleContext';
import ProtectedRoute from './components/ProtectedRoute';
import LocaleRoute from './components/LocaleRoute';
import { routes } from './config/routes';
import Login from './pages/Login';
import CustomerDashboard from './pages/customer/Dashboard';
import EmployeeDashboard from './pages/employee/Dashboard';
import TransactionHistory from './pages/customer/TransactionHistory';
import TransferMoney from './pages/customer/TransferMoney';
import ChangePassword from './pages/customer/ChangePassword';
import EmployeeChangePassword from './pages/employee/ChangePassword';
import EmployeeTransactionHistory from './pages/employee/TransactionHistory';
import EmployeeSelectCustomer from './pages/employee/SelectCustomer';
import DepositMoney from './pages/employee/DepositMoney';
import OpenAccountNewCustomer from './pages/employee/OpenAccountNewCustomer';
import OpenAccountExistingCustomer from './pages/employee/OpenAccountExistingCustomer';
import CloseAccount from './pages/employee/CloseAccount';
import IssueDebitCard from './pages/employee/IssueDebitCard';
import BlockDebitCard from './pages/employee/BlockDebitCard';
import CustomerBlockDebitCard from './pages/customer/BlockDebitCard';

function App() {
  return (
    <BrowserRouter>
      <LocaleProvider>
        <AuthProvider>
          <Routes>
            {/* Root redirect to Polish login */}
            <Route path="/" element={<Navigate to="/pl/logowanie" replace />} />
            
            {/* English routes */}
            <Route path="/en" element={<LocaleRoute />}>
              <Route path={routes.login.en} element={<Login />} />
              
              {/* Customer Routes */}
              <Route
                path={routes.customer.dashboard.en}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <CustomerDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.transactionHistory.en}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <TransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.transfer.en}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <TransferMoney />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.changePassword.en}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <ChangePassword />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.blockDebitCard.en}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <CustomerBlockDebitCard />
                  </ProtectedRoute>
                }
              />

              {/* Employee Routes */}
              <Route
                path={routes.employee.dashboard.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.selectCustomer.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeSelectCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.transactionHistoryByCustomer.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeTransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.transactionHistoryByAccount.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeTransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.deposit.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <DepositMoney />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.openAccountNew.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <OpenAccountNewCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.openAccountExisting.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <OpenAccountExistingCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.closeAccount.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <CloseAccount />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.issueDebitCard.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <IssueDebitCard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.blockDebitCard.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <BlockDebitCard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.changePassword.en}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeChangePassword />
                  </ProtectedRoute>
                }
              />
            </Route>

            {/* Polish routes */}
            <Route path="/pl" element={<LocaleRoute />}>
              <Route path={routes.login.pl} element={<Login />} />
            
              {/* Customer Routes */}
              <Route
                path={routes.customer.dashboard.pl}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <CustomerDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.transactionHistory.pl}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <TransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.transfer.pl}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <TransferMoney />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.changePassword.pl}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <ChangePassword />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.customer.blockDebitCard.pl}
                element={
                  <ProtectedRoute role="CUSTOMER">
                    <CustomerBlockDebitCard />
                  </ProtectedRoute>
                }
              />

              {/* Employee Routes */}
              <Route
                path={routes.employee.dashboard.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.selectCustomer.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeSelectCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.transactionHistoryByCustomer.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeTransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.transactionHistoryByAccount.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeTransactionHistory />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.deposit.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <DepositMoney />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.openAccountNew.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <OpenAccountNewCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.openAccountExisting.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <OpenAccountExistingCustomer />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.closeAccount.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <CloseAccount />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.issueDebitCard.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <IssueDebitCard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.blockDebitCard.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <BlockDebitCard />
                  </ProtectedRoute>
                }
              />
              <Route
                path={routes.employee.changePassword.pl}
                element={
                  <ProtectedRoute role="EMPLOYEE">
                    <EmployeeChangePassword />
                  </ProtectedRoute>
                }
              />
            </Route>
          </Routes>
        </AuthProvider>
      </LocaleProvider>
    </BrowserRouter>
  );
}

export default App;
