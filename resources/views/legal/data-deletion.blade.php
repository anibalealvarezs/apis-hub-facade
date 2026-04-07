@extends('legal.layout')

@section('title', 'Data Deletion Instructions')

@section('content')
    <h1>DATA DELETION INSTRUCTIONS</h1>

    <p>APIs Hub does not store your personal data permanently on our servers unless it is strictly necessary for the operation of the services you have explicitly requested (such as your Ad Account IDs or Email for authentication).</p>

    <p>If you wish to delete your activities or personal data from the APIs Hub Application, you can do so by following these simple steps:</p>

    <ol>
        <li>Send an email to <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a> with the subject <strong>"Data Deletion Request"</strong>.</li>
        <li>In the body of the email, please provide the <strong>Email Address</strong> associated with your APIs Hub account.</li>
        <li>Our team will process your request within <strong>48-72 hours</strong>.</li>
        <li>Once processed, we will permanently delete all your associated records from our databases and revoke any active Access Tokens connected to Meta APIs.</li>
    </ol>

    <h2>What data will be deleted?</h2>
    <ul>
        <li>All Ad Account associations.</li>
        <li>Meta Access Tokens and Refresh Tokens.</li>
        <li>Cached reporting metrics associated with your account.</li>
        <li>User profile information (Name and Email).</li>
    </ul>

    <h2>Project Suspension and Retention</h2>
    <p>When a project is permanently suspended, APIs Hub maintains the associated data for a safety period of <strong>30 days</strong>. This ensures that data can be recovered in case of accidental suspension or if the user changes their mind.</p>
    <ul>
        <li>After the 30-day period expires, all data is automatically and permanently deleted.</li>
        <li>If a user requests explicit deletion via email, the process is expedited and completed within <strong>24 to 72 hours</strong>.</li>
        <li>User accounts (profiles) are not deleted automatically when a project is suspended; they are only removed upon explicit request via email.</li>
    </ul>

    <h2>Manual Social Logout</h2>
    <p>Additionally, you can always remove APIs Hub's access to your data directly through your APIs Hub project's settings:</p>

    <ol>
        <li>Go to the Synchronization Settings section of the project and disconnect the social media accounts by clicking on the logout button.</li>
    </ol>

    <p>For any further questions, please contact our support team at <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a>.</p>
@endsection
