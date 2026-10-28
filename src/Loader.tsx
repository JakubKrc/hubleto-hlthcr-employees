import React from 'react';
import App from '@hubleto/react-ui/core/App'
import Translator from '@hubleto/react-ui/core/Translator';
import FormCustomizer from '@hubleto/react-ui/core/FormCustomizer';
import { type FormMeta } from '@hubleto/react-ui/components/fc/FormInterfaces';
import TableEmployees from './Components/FC/TableEmployees'

const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEmployees';
const T = new Translator(parentApp + '/Loader', 'Loader');

// "Zamestnanci" tab in the branch form (HlthcrBranches).
function mountBranchEmployeesTab() {
  return {
    title: T.translate('Employees'),
    content: (form: FormMeta) => {
      if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Save the branch first.')}</div>;
      return <TableEmployees uid={form.uid + '_table_employees'} parentForm={form} idBranch={form.id} />;
    },
  };
}

class HlthcrEmployeesApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('HlthcrEmployeesTableEmployees', TableEmployees);

    FormCustomizer.addTab('FormBranch', 'hlthcr-employees', mountBranchEmployeesTab);
  }
}

globalThis.hubleto.registerApp(parentApp, new HlthcrEmployeesApp());
