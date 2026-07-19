<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme-projects')->firstOrFail();

        // Checklist for "Finalize product positioning document"
        $todo1 = Todo::where('title', 'Finalize product positioning document')->first();
        if ($todo1) {
            $cl1 = Checklist::create(['todo_id' => $todo1->id, 'name' => 'Review Steps', 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl1->id, 'content' => 'Read through draft positioning', 'is_checked' => true, 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl1->id, 'content' => 'Verify competitor analysis data', 'is_checked' => true, 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl1->id, 'content' => 'Get stakeholder sign-off', 'is_checked' => false, 'position' => 2]);
            ChecklistItem::create(['checklist_id' => $cl1->id, 'content' => 'Final polish and formatting', 'is_checked' => false, 'position' => 3]);
        }

        // Checklist for "Design new landing page mockups"
        $todo2 = Todo::where('title', 'Design new landing page mockups')->first();
        if ($todo2) {
            $cl2 = Checklist::create(['todo_id' => $todo2->id, 'name' => 'Design Phases', 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl2->id, 'content' => 'Wireframe complete', 'is_checked' => true, 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl2->id, 'content' => 'High-fidelity mockups', 'is_checked' => true, 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl2->id, 'content' => 'Responsive variants', 'is_checked' => false, 'position' => 2]);
            ChecklistItem::create(['checklist_id' => $cl2->id, 'content' => 'Prototype interactions', 'is_checked' => false, 'position' => 3]);

            $cl3 = Checklist::create(['todo_id' => $todo2->id, 'name' => 'Stakeholder Feedback', 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl3->id, 'content' => 'Share with design lead', 'is_checked' => true, 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl3->id, 'content' => 'Incorporate CEO feedback', 'is_checked' => false, 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl3->id, 'content' => 'Final approval', 'is_checked' => false, 'position' => 2]);
        }

        // Checklist for "Set up Kubernetes cluster"
        $todo3 = Todo::where('title', 'Set up Kubernetes cluster')->first();
        if ($todo3) {
            $cl4 = Checklist::create(['todo_id' => $todo3->id, 'name' => 'Infrastructure Setup', 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl4->id, 'content' => 'Provision EKS cluster', 'is_checked' => true, 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl4->id, 'content' => 'Configure node groups', 'is_checked' => true, 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl4->id, 'content' => 'Set up IAM roles and policies', 'is_checked' => true, 'position' => 2]);
            ChecklistItem::create(['checklist_id' => $cl4->id, 'content' => 'Configure auto-scaling', 'is_checked' => false, 'position' => 3]);
            ChecklistItem::create(['checklist_id' => $cl4->id, 'content' => 'Deploy monitoring stack', 'is_checked' => false, 'position' => 4]);
        }

        // Checklist for "Implement push notification service"
        $todo4 = Todo::where('title', 'Implement push notification service')->first();
        if ($todo4) {
            $cl5 = Checklist::create(['todo_id' => $todo4->id, 'name' => 'Platform Setup', 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl5->id, 'content' => 'Register with FCM', 'is_checked' => true, 'position' => 0]);
            ChecklistItem::create(['checklist_id' => $cl5->id, 'content' => 'Register with APNs', 'is_checked' => false, 'position' => 1]);
            ChecklistItem::create(['checklist_id' => $cl5->id, 'content' => 'Build notification service API', 'is_checked' => false, 'position' => 2]);
        }

        $this->command->info('Created checklists with items.');
    }
}
