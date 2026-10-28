import Translator from '@hubleto/react-ui/core/Translator';
import Form from '@hubleto/react-ui/components/fc/Form';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import { useRecordField } from '@hubleto/react-ui/components/fc/FormRecordStore';

const componentName = 'FormEmployee';
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEmployees';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

// The job dropdown offers only jobs of the selected branch. Hubleto FC lookups do not send the form values,
// so the branch goes into the lookup request ("myself" is how FC inputs accept extra request params).
const InputJobOfSelectedBranch = () => {
  const idBranch = useRecordField('id_branch', 0);
  return <Input
    key={'job-of-branch-' + idBranch}
    field='id_job'
    customInputProps={{myself: {customEndpointParams: {idBranch: idBranch}}}}
  />;
}

const TabDefault = () => <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
  <div>
    <h3 className='text-lg mb-2'>{T.translate('Personal data')}</h3>
    <Input field='name' />
    <Input field='date_of_birth' />
    <Input field='personal_number' />
    <Input field='street' />
    <Input field='city' />
    <Input field='postal_code' />
    <Input field='email' />
    <Input field='phone' />
  </div>
  <div>
    <h3 className='text-lg mb-2'>{T.translate('Employment')}</h3>
    <Input field='id_branch' />
    <InputJobOfSelectedBranch />
    <Input field='date_hired' />
    <Input field='date_terminated' />
    <Input field='is_active' />
    <Input field='note' />
  </div>
</div>;

const FormEmployee = (props: FormProps) => <Form
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/Employee'}
  urlSlug='hlthcr-employees/employees'
  title={{field: 'name', sub: T.translate('Employee')}}
  tabs={{default: {title: <b>{T.translate('Employee')}</b>, content: () => <TabDefault />}}}
  {...props}
/>;

export default FormEmployee;
