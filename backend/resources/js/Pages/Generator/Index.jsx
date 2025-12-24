import React from 'react';
import { useForm } from '@inertiajs/react';
import Layout from '@/Layouts/Layout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

const Index = () => {
  const { data, setData, post, processing, errors } = useForm({
    ddl_file: null,
  });

  function submit(e) {
    e.preventDefault();
    post(route('schema.store'));
  }

  return (
    <div className="flex justify-center items-center h-full">
      <Card className="w-full max-w-lg">
        <CardHeader>
          <CardTitle>Upload SQL Schema</CardTitle>
          <CardDescription>
            Upload a SQL DDL file (`.sql`) to define the schema for data generation. 
            The file should contain `CREATE TABLE` statements.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-4">
            <div>
              <Input
                id="ddl_file"
                type="file"
                onChange={(e) => setData('ddl_file', e.target.files[0])}
                className="file:text-white"
              />
              {errors.ddl_file && <p className="text-red-500 text-xs mt-1">{errors.ddl_file}</p>}
            </div>
            <Button disabled={processing} className="w-full">
              {processing ? 'Uploading...' : 'Upload and Configure'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
};

Index.layout = page => <Layout children={page} />;

export default Index;
